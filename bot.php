<?php

include __DIR__.'/vendor/autoload.php';
include 'config.inc';
include 'commands.php';

use Discord\Discord;
use Discord\Parts\Channel\Message;
use Discord\WebSockets\Intents;
use Discord\WebSockets\Event;
use Discord\Parts\User\Game;
use Discord\Parts\User\Activity;
use Discord\Parts\Embed;

$commands = new Commands($keys, floor(microtime(true) * 1000));

$discord = new Discord([
    'token' => $keys['discord'],
    'intents' => Intents::getDefaultIntents() | Intents::MESSAGE_CONTENT | Intents::GUILD_MEMBERS | Intents::GUILD_PRESENCES,
	'logger' => new \Monolog\Logger('New logger'),
	'loadAllMembers' => true,
]);

$discord->on('ready', function (Discord $discord) use ($commands) {
	
    echo "Bot is ready!\n";
	
	$activity = $discord->factory(Activity::class, [
		'name' => 'The Fappening',
		'type' => Activity::TYPE_STREAMING,
	]);
	$discord->updatePresence($activity);

    $discord->on(Event::MESSAGE_CREATE, function (Message $message, Discord $discord) use ($commands) {
		
        echo "(".date("d/m h:i:sA").") [{$message->channel->name}] {$message->author->username}: {$message->content}\n";
		
		if (@$message->content[0] == "!" && @$message->content[1] != " " && !$message->author->bot && strlen(@$message->content) >= 2) { 
			$commands->execute($message, $discord);
		}
		
    });
	
});

$discord->run();

?>