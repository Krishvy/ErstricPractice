<?php

declare(strict_types=1);

namespace Krishvy\queue\duel;

use Krishvy\Erstric;
use Krishvy\session\SessionFactory;
use Krishvy\utils\Utils;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class Duel
{
    private static int $count = 5;
    private Player $p;
    private Player $p2;
    private static string $kit;
    private static bool $isRanked;
    public array $nodebuffMaps =
        [
            "put ur map here"
        ];
    public array $dragonMaps =
        [
            "put ur map here"
        ];
    public array $freeshopMaps =
        [
            "put ur map here"
        ];
    public array $boxingMaps =
        [
            "put ur map here"
        ];

    public function __construct(Player $p, Player $p2, string $kit, bool $ranked = false)
    {
        self::$kit = $kit;
        self::$isRanked = $ranked;
        $worldName = str_shuffle("abcdefghijklmnopqrstuvwxyz");
        $this->p2 = $p2;
        $this->p = $p;
        $s1 = SessionFactory::getSession($p);
        $s2 = SessionFactory::getSession($p2);
        var_dump($kit);
        $s1->setInDuel($this);
        $s2->setInDuel($this);
        var_dump($s1->getInDuel());
        var_dump($s2->getInDuel());
        $s1->kit($kit);
        $s2->kit($kit);
        $pluginDataFolder = Erstric::getInstance()->getDataFolder();
        $serverDataFolder = Server::getInstance()->getDataPath();
        $map = null;
        $pcoordinates = null;
        $p2coordinates = null;
        switch($kit){
            case 'nodebuff':
                $map = $this->nodebuffMaps[array_rand($this->nodebuffMaps)];
                $pcoordinates = $this->getCoordinatesForMap('nodebuff-'.$map);
                $p2coordinates = $this->getCoordinatesForPlayer2('nodebuff-'.$map);
                break;
            case 'sumo':
                break;
            case "freeshop":
                $map = $this->freeshopMaps[array_rand($this->freeshopMaps)];
                $pcoordinates = $this->getCoordinatesForPlayer2('freeshop-'.$map);
                $p2coordinates = $this->getCoordinatesForPlayer2('freeshop-'.$map);
                break;
            case "dragon":
                $map = $this->dragonMaps[array_rand($this->dragonMaps)];
                $pcoordinates = $this->getCoordinatesForMap('dragon-'.$map);
                $p2coordinates = $this->getCoordinatesForPlayer2('dragon-'.$map);
                break;
            case "boxing":
                var_dump('boxing');
                $map = $this->boxingMaps[array_rand($this->boxingMaps)];
                $pcoordinates = $this->getCoordinatesForMap('boxing-'.$map);
                $p2coordinates = $this->getCoordinatesForPlayer2('boxing-'.$map);
                break;
        }
        copy($pluginDataFolder.DIRECTORY_SEPARATOR.'arenas'.DIRECTORY_SEPARATOR.$kit.DIRECTORY_SEPARATOR.$map, $serverDataFolder.DIRECTORY_SEPARATOR.'worlds'.DIRECTORY_SEPARATOR.$worldName);
        $p->teleport($pcoordinates, $map);
        $p2->teleport($p2coordinates, $map);
        $p->setImmobile();
        $p2->setImmobile();
        $task = new ClosureTask(function () use ($p, $p2) {
            if (self::$count >= 1 && self::$count <= 5) {
                $p->sendTitle(TextFormat::AQUA.self::$count);
                $p2->sendTitle(TextFormat::AQUA.self::$count);
            }
           self::$count--;
            if(self::$count === 0){
                $p->sendTitle(TextFormat::AQUA."Fight!");
                $p2->sendTitle(TextFormat::AQUA."Fight!");
                $p2->setImmobile(false);
                $p->setImmobile(false);
            }
        });
        Erstric::getInstance()->getScheduler()->scheduleRepeatingTask($task, 20);
    }

    public function getKit(): string
    {
        return self::$kit;
    }

    public function win(Player $winner, Player $loser): void
    {
        $s1 = SessionFactory::getSession($winner);
        $s2 = SessionFactory::getSession($loser);
        $s1->clear();
        $s2->clear();
        $winner->sendMessage("§aYou won the duel!");
        $loser->sendMessage("§cYou lost the duel!");
        $winner->sendTitle("§aVICTORY");
        $loser->sendTitle("§cDEFEAT");
        Server::getInstance()->broadcastMessage(TextFormat::GREEN."{$winner->getName()} ".TextFormat::GRAY."won a ".self::$kit." duel against ".TextFormat::RED."{$loser->getName()}");
        Utils::teleportToLobby($winner);
        Utils::teleportToLobby($loser);
        $s1->lobbyItems();
        $s2->lobbyItems();
        $s1->setInDuel(null);
        $s2->setInDuel(null);
        if(self::$isRanked){
            $add = mt_rand(3, 18);
            $s1->setStatistics('elo', $s1->getStatistics()['elo'] + $add);
            $s2->setStatistics('elo', $s2->getStatistics()['elo'] - $add);
            $winner->sendMessage(TextFormat::GREEN."You gained ".TextFormat::GRAY.$add.TextFormat::GREEN." elo");
            $loser->sendMessage(TextFormat::RED."You lost ".TextFormat::GRAY.$add.TextFormat::RED." elo");
        }
    }

    public function getPlayers() : array {
        return [$this->p, $this->p2];
    }

    public function getCoordinatesForMap(string $map): Vector3
    {
        return match ($map) {
            "put ur map here" => new Vector3(317, 68, 265),
            "put ur map here" => new Vector3(154, 59, 225),
            "put ur map here" => new Vector3(293, 66, 258),
            "put ur map here" => new Vector3(254, 63, 291),
            'put ur map here' => new Vector3(223, 60, 255),
            'put ur map here' => new Vector3(276, 67, 247),
            'put ur map here' => new Vector3(182, 61, 285),
            'put ur map here' => new Vector3(204, 76, 166),
            'put ur map here' => new Vector3(284, 52, 341),
            'put ur map here' => new Vector3(167, 55, 249),
            default => null,
        };
    }

    public function getCoordinatesForPlayer2(string $map): Vector3
    {
        return match ($map) {
            "put ur map here" => new Vector3(317, 68, 265),
            "put ur map here" => new Vector3(154, 59, 225),
            "put ur map here" => new Vector3(293, 66, 258),
            "put ur map here" => new Vector3(254, 63, 291),
            'put ur map here' => new Vector3(223, 60, 255),
            'put ur map here' => new Vector3(276, 67, 247),
            'put ur map here' => new Vector3(182, 61, 285),
            'put ur map here' => new Vector3(204, 76, 166),
            'put ur map here' => new Vector3(284, 52, 341),
            'put ur map here' => new Vector3(167, 55, 249),
            default => null,
        };
    }

}