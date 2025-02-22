<?php

use Discord\Parts\Embed\Embed;
use Carbon\Carbon;

class Commands {
	
	public $keys;
	public $uptime;
	
	function __construct($keys, $uptime) {
		
		$this->keys = $keys;
		$this->uptime = $uptime;
		
	}
	
	function execute($message, $discord) {
		
		$inputs = explode(" ", strtolower(trim($message->content)));
		$command = substr($inputs[0], 1);
		array_shift($inputs);
		$args = implode(" ", $inputs);
		
		switch ($command) {
			
			case "ping":
				$message->reply("Pong!");
				break;
				
			case (preg_match('/^(kate|t(?:ay(lor)?|swizzle)|emma|e?liz(abeth)?|olympia|olivia|kim|mckayla|zach|hilary|ronan|sydney)\b/', $command, $babe) ? true : false):
				$this->sendBabe($babe, $message);
				break;
				
			case (preg_match('/^(search|google|bing|find|siri)/', $command) ? true : false):
				$this->search('google', $args, $message);
				break;
				
			case (preg_match('/^(image|img|photo|pic)/', $command) ? true : false):
				$this->search('image', $args, $message);
				break;
				
			case (preg_match('/^(ban|kick|sb|sinbin)/', $command) ? true : false):
				$this->sinbin($args, $message, $discord);
				break;
			
			case (preg_match('/^(bard|(open)?ai)/', $command) ? true : false):
				$this->bard($args, $message, $discord);
				break;
				
			case (preg_match('/^(asx|share(s)?|stock(s)?|etf)/', $command) ? true : false):
				$this->ASX($args, $message, $discord);
				break;
				
			case (preg_match('/^(weather|temp(erature)?)$/', $command) ? true : false):
				$this->weather($message);
				break;
				
			case (preg_match('/^(shell|bash|cli|cmd)/', $command) ? true : false):
				$this->runcli($args, $message, $discord);
				break;
				
			case "apex":
				$this->apex($message, $discord);
				break;
				
			case "uptime":
				$this->uptime($message);
				break;

			case "remindme":
				$this->remindMe($args, $message);
				break;
		
		}
		
	}

	//RemindMe function, by @bgoold
	function remindMe($message) {
		//split message into parts
		$parts = explode(" ", $message);

		//check for the correct number of parameters
		if(count($parts) >= 2) {
			//parse the duration from user message - parts 0 and 1
			$interval = $parts[0];
			//check that its a valid number
			if(!is_numeric($interval) || $interval <= 0 )
				return $message->reply("Please use a valid number of minutes, days, weeks or months.");
			
			//check that its a valid unit
			$units = $parts[1];
			if($units != "minute" && $units != "mins" && $units != "minutes" && $units != "hour" && $units != "hr" && $units != "hrs" && $units != "hours" && $units != "day" && $units != "days" && $units != "week" && $units != "weeks" && $units != "month" && $units != "months" && $units != "year" )
				return $message->reply("Please use a valid unit of time (eg: minutes, hours or days).");

			$timeSlice = array_slice($parts,0,1);
			$time = implode(" ",$timeSlice);

			//calculate reminder timestamp
			$timestamp = strtotime("+" . $time);

			//if it's more than 1 yr in the future, don't allow it
			if($timestamp > strtotime("+1 year"))
				return $message->reply("Reminders greater than one year in the future are not allowed.");

			//store any optional message if it exists
			$userMsg = null; //idk how to handle dynamic variables in PHP so I'm just setting this to null for safety
			if(count($parts) > 2) {
				$slice = array_slice($parts,2,count($parts));
				$userMsg = implode(" ", $slice);
			}

			//TODO @Buzz: 1. store $timestamp and $userMsg in a database, include a column to denote whether a reminder has been actioned
			// 2. have a cron job check every minute for unactioned reminders on the current minute or in the past
			// 3. if a reminder in the result has not been actioned, update the actioned column and send a message to the chat (eg: return $message->reply("[Reminder] $userMsg");)
			//
			//alternatively use a timer library but that won't work if bot goes down			
			
		} else {
			return $message->reply("Please specify when you would like to be reminded with the format <interval> <units> <message> (eg: 12 hours Dota time)");
		}
	}
	
	function sendBabe($babe, $message) {
	
		$imgDir = "/home/buzz/img/".preg_replace(array('/e?liz(abeth)?\b/', '/t(ay)?(lor)?(swizzle)?\b/'), array('elizabeth', 'taylor'), $babe[0]);
		$files = (is_dir($imgDir)) ? scandir($imgDir) : null;
		if ($files) { 
			$message->channel->sendFile("{$imgDir}/{$files[rand(2,(count($files) - 1))]}", $babe[0].".jpg");
		}
		
	}
	
	function search($type, $args, $message) {
	
		if (empty($args)) { return $message->reply("Maybe give me something to search for??"); }
		
		$search = ($type == "google") ? @file_get_contents("https://www.googleapis.com/customsearch/v1?key={$this->keys['google']}&cx=017877399714631144452:hlos9qn_wvc&googlehost=google.com.au&num=1&q=".str_replace(' ', '%20', $args)) : @file_get_contents("https://www.googleapis.com/customsearch/v1?key={$this->keys['google']}&cx=017877399714631144452:0j02gfgipjq&googlehost=google.com.au&searchType=image&excludeTerms=youtube&imgSize=xxlarge&safe=off&num=1&fileType=jpg,png,gif&q=".str_replace(' ', '%20', $args)."%20-site:facebook.com%20-site:tiktok.com%20-site:instagram.com");
		
		$return = json_decode($search);
		
		if ($return->searchInformation->totalResults == 0) { return $message->reply("No results."); }
		
		return ($type == "google") ? $message->channel->sendMessage("{$return->items[0]->title}: {$return->items[0]->link}") : $message->channel->sendMessage($return->items[0]->link);
	
	}
	
	function bard($args, $message, $discord) {
		
		if (empty($args)) { return $message->reply("Maybe give the AI something to do??"); }
		
		$tokens = ($this->isAdmin($message->author->id, $discord)) ? 1500 : 200;
		
		$post_fields = array(
			"maxOutputTokens" => $tokens,
			"prompt" => array(
				"text" => "Limiting yourself to 3 sentences, and without using a list: ".$args
			),
			"safetySettings" => array(
				array(
					"category" => "HARM_CATEGORY_VIOLENCE",
					"threshold" => "BLOCK_NONE"
				),
				array(
					"category" => "HARM_CATEGORY_DEROGATORY",
					"threshold" => "BLOCK_NONE"
				),
				array(
					"category" => "HARM_CATEGORY_TOXICITY",
					"threshold" => "BLOCK_NONE"
				),
				array(
					"category" => "HARM_CATEGORY_SEXUAL",
					"threshold" => "BLOCK_NONE"
				),
				array(
					"category" => "HARM_CATEGORY_UNSPECIFIED",
					"threshold" => "BLOCK_NONE"
				),
				array(
					"category" => "HARM_CATEGORY_MEDICAL",
					"threshold" => "BLOCK_NONE"
				),
				array(
					"category" => "HARM_CATEGORY_DANGEROUS",
					"threshold" => "BLOCK_NONE"
				)
			)
		);
		
		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_URL => 'https://generativelanguage.googleapis.com/v1beta2/models/text-bison-001:generateText?key='.$this->keys['bard'],
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => '',
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 0,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => 'POST',
			CURLOPT_POSTFIELDS => json_encode($post_fields),
			CURLOPT_HTTPHEADER => array(
				'Content-Type: application/json'
			),
		));
		
		$response = json_decode(curl_exec($curl));
		curl_close($curl);
		
		if (@$response->error->message) { return $message->reply($response->error->message); }
		
		else if (@$response->filters[0]->reason) { 
		
			if ($response->filters[0]->reason == "SAFETY") {
				
				return $message->reply("Error Reason: ".$response->filters[0]->reason." (".$response->safetyFeedback[0]->rating->category." -> ".$response->safetyFeedback[0]->rating->probability.")"); 
				
			}
			
			return $message->reply("Error Reason: ".$response->filters[0]->reason); 
			
		}
		
		$string = (strlen($response->candidates[0]->output) > 1995) ? substr($response->candidates[0]->output,0,1995).'…' : $response->candidates[0]->output;

		$message->channel->sendMessage($string);
		
	}
	
	function weather($message) {
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "https://api.weather.bom.gov.au/v1/locations/r1ppvy/observations");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/108.0.0.0 Safari/537.36");
		$temp = json_decode(curl_exec($ch));
		$message->channel->sendMessage("{$temp->data->temp}° (Feels {$temp->data->temp_feels_like}°) | Wind: {$temp->data->wind->speed_kilometre}kph ".preg_replace(array('/^N$/', '/^S$/', '/^E$/', '/^W$/', '/^.?NE$/', '/^.?SE$/', '/^.?SW$/', '/^.?NW$/'), array('↓', '↑', '←', '→', '↙', '↖', '↗', '↘'), $temp->data->wind->direction)." | Humidity: {$temp->data->humidity}% | Rain: {$temp->data->rain_since_9am}mm");
		
	}
	
	function uptime($message) {
		
		$diff = (floor(microtime(true) * 1000) - $this->uptime) / 1000;
		$days = floor($diff / 86400);
		$diff -= $days * 86400;
		$hours = floor($diff / 3600) % 24;
		$diff -= $hours * 3600;
		$minutes = floor($diff / 60) % 60;
		$diff -= $minutes * 60;
		$seconds = floor($diff % 60);
		$message->reply("{$days} days, {$hours} hrs, {$minutes} mins, {$seconds} secs");
		
	}
	
	function ASX($args, $message, $discord) {
		
		if (empty($args) || strlen($args) > 4) { return $message->reply("Try !asx DMP"); }
		
		if (false === ($header = @file_get_contents("https://asx.api.markitdigital.com/asx-research/1.0/companies/{$args}/header"))) {
			return $message->reply("Invalid search. Try !asx DMP"); 
		}
		
		$asxInit = json_decode($header);
		$asx["Current Price"] = "$".number_format($asxInit->data->priceLast, 2);
		$asx["Change"] = number_format($asxInit->data->priceChangePercent, 2)."%";
		$asx["Name"] = $asxInit->data->displayName;
		$asx["URL"] = "https://www2.asx.com.au/markets/company/{$args}";
		$asx["Market Cap"] = ($asxInit->data->securityType == 7) ? "ETF" : "$".number_format($asxInit->data->marketCap);
		$key = json_decode(file_get_contents("https://asx.api.markitdigital.com/asx-research/1.0/companies/{$args}/key-statistics"));
		$asx["52W ↑ / ↓"] = "$".$key->data->priceFiftyTwoWeekHigh." / $".$key->data->priceFiftyTwoWeekLow;
		$asx["Earnings Per Share"] = (!$key->data->earningsPerShare) ? "ETF" : "$".$key->data->earningsPerShare;
		$asx["Annual Yield"] = (!$key->data->yieldAnnual) ? "ETF" : number_format($key->data->yieldAnnual, 2)."%";
		
		if ($asx["Market Cap"] == "ETF") {
			$keyETF = json_decode(file_get_contents("https://asx.api.markitdigital.com/asx-research/1.0/etfs/{$args}/key-statistics"));
			$asx["NAV"] = "$".$keyETF->data->shareInformation->nav;
			$asx["YTD Return"] = $keyETF->data->fundamentals->returnYearToDate."%";
			$asx["Mgmt Fee"] = $keyETF->data->fundamentals->managementFeePercent."%";
			$asx["URL"] = "https://www2.asx.com.au/markets/etp/{$args}";
		}
		
		$embed = $discord->factory(Embed::class);
		$embed->setTitle($asx["Name"])
			->setURL($asx["URL"])
			->setDescription("ASX : ".strtoupper($args))
			->setColor("0x00A9FF")
			->setTimestamp()
			->setFooter("ASX", "https://www2.asx.com.au/content/dam/asx/asx-logos/asx-brandmark.png");
		
		foreach ($asx as $key => $value) {		
			if ($key == "Name" || $key == "URL" || $value == "ETF" ) { }
			else {	
				$embed->addFieldValues("{$key}", "{$value}", true);
			}
		}
		
		$message->channel->sendEmbed($embed);
	}
	
	function sinbin($args, $message, $discord) {
		
		if (empty($args)) { return $message->reply("Try !sinbin @username"); }
		
		if ($this->isAdmin($message->author->id, $discord)) {
			$argz = explode(" ", $args);
			$sbID = str_replace(array('<','@','!','>', '&'),'', $argz[0]);
		 	$sbGuild = $discord->guilds->get('id', '232691831090053120');
			$sbMember = $sbGuild->members->get('id', strval($sbID));
			$time = (count($argz) <= 1) ? 1 : $argz[1];
			$sbMember->timeoutMember(new Carbon("{$time} minutes"))->done(function () {});
			$message->channel->sendMessage("{$argz[0]} has been given a {$time} minute timeout");
		}
		
	}
	
	function runcli($args, $message, $discord) {
		
		if ($message->author->id == 232691181396426752 && !empty($args)) {		
			$message->channel->sendMessage("```swift\n".shell_exec($args)."\n```");		
		}
		
	}
	
	function isAdmin($userID, $discord) {
		
		$testGuild = $discord->guilds->get('id', '232691831090053120');
		$testMember = $testGuild->members->get('id', $userID);
		return $testMember->roles->has('232692759557832704');
		
	}
	
	function apex($message, $discord) {
		
		$get = file_get_contents("https://apexlegendsstatus.com/current-map/battle_royale/pubs");
		preg_match('/<h3 .*>(.+)<\/h3>.+ ends in (.+)<\/p>/U', $get, $data);
		preg_match_all('/<h3 .*>(.+)<\/h3>/U', $get, $next);
	
		$message->channel->sendMessage($data[1]." ends in ".$data[2]." | Next Map: ".$next[1][1]);
	}
	
}

?>