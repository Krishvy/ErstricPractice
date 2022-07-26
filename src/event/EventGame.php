<?php

declare(strict_types=1);

namespace Krishvy\event;

use Krishvy\Erstric;
use pocketmine\player\Player;
use pocketmine\scheduler\CancelTaskException;
use pocketmine\scheduler\Task;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class EventGame
{
    private array $players = [];

    public function __construct(Player $player){
        $this->players[] = $player;
        Server::getInstance()->broadcastMessage(TextFormat::GRAY."A new sumo event has been started by ".$player->getName());
        Server::getInstance()->broadcastMessage(TextFormat::GRAY."Use the totem item at spawn to join the event!");
        Erstric::getInstance()->getScheduler()->scheduleRepeatingTask(new class() extends Task {
            private int $count = 120;
            public function onRun(): void
            {
                if($this->count === 0){
                    Server::getInstance()->broadcastMessage(TextFormat::GRAY."The event has started!");
                   throw new CancelTaskException();
                }
                if ($this->count >= 0 && $this->count <= 10) {
                    Server::getInstance()->broadcastMessage(TextFormat::GRAY."The event will start in ".$this->count." seconds!");
                }
                $this->count--;
                // todo
            }
        }, 20);
    }
}
