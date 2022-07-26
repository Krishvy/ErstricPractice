<?php

declare(strict_types=1);

namespace Krishvy\commands;

use Krishvy\utils\Utils;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;

class RestartCommand extends Command
{
    public function __construct()
    {
        parent::__construct("restart", "Restart the server", "", ["reboot"]);
        parent::setPermission('skyetri.restart');
    }
    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        Utils::setRestart();
    }
}