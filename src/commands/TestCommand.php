<?php

declare(strict_types=1);

namespace Krishvy\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class TestCommand extends Command
{
    public function __construct()
    {
        parent::__construct('test', 'test');
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void
    {
        if (!$sender instanceof Player) {
            return;
        }
        $sender->sendActionBarMessage('test');
    }
}
