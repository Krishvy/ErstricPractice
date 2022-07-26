<?php

declare(strict_types=1);

namespace Krishvy\commands;

use Krishvy\session\SessionFactory;
use Krishvy\utils\Utils;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class SpawnCommand extends Command
{
    public function __construct()
    {
        parent::__construct("spawn", "Teleport to spawn");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void
    {
        if (!$sender instanceof Player) {
            return;
        }
        $sender->teleport($sender->getServer()->getWorldManager()->getWorldByName("lobby")->getSafeSpawn());
        SessionFactory::getSession($sender)->lobbyItems();
    }

}
