<?php

declare(strict_types=1);

namespace Krishvy\queue;

use Krishvy\queue\duel\Duel;
use pocketmine\block\utils\DyeColor;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class Queue
{
    private static array $queuedPlayers = [];

    public static function placeInQueue(Player $player, string $kit, bool $ranked = false): void
    {
        if (!isset(self::$queuedPlayers[$player->getName()])) {
            self::$queuedPlayers[$player->getName()] = [
                "kit" => $kit,
                "ranked" => $ranked
            ];
            $player->sendMessage(TextFormat::GREEN . "Successfully joined queue.");
            $player->getInventory()->clearAll();
            $player->getInventory()->addItem(VanillaItems::DYE()->setColor(DyeColor::RED())->setCustomName('§cLeave Queue'));
                    if(self::getQueueCount($kit) === 2){
                        new Duel($player, $player->getServer()->getPlayerByPrefix(array_keys(self::$queuedPlayers)[0]), self::$queuedPlayers[$player->getName()]["kit"], self::$queuedPlayers[$player->getName()]["ranked"]);
                        var_dump(self::$queuedPlayers);
                        self::$queuedPlayers = [];
                    }
        }
    }

    public static function getEveryoneInQueues() : int
    {
        return count(self::$queuedPlayers);
    }

    static function getQueueCount(string $kit): int
    {
        $count = 0;
        foreach(self::$queuedPlayers as $player) {
            if($player["kit"] === $kit) {
                $count++;
            }
        }
        return $count;
    }

    static function removeFromQueue(Player $player, $notify = true): void
    {
        if(isset(self::$queuedPlayers[$player->getName()])) {
            unset(self::$queuedPlayers[$player->getName()]);
        }
        if($notify){
            $player->sendMessage(TextFormat::RED.'You have been removed from the queue.');
        }
    }
}