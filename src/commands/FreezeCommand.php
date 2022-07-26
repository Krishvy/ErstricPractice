<?php

declare(strict_types=1);

namespace Krishvy\commands;

use Krishvy\session\SessionFactory;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class FreezeCommand extends Command
{
    public function __construct()
    {
        parent::__construct("freeze", "Freeze a player", "Usage: /freeze <player>");
        parent::setPermission('skyetri.freeze');
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void
    {
        if (!$sender instanceof Player) {
            return;
        }
        if (!isset($args[0])) {
            $sender->sendMessage(TextFormat::RED . "Usage: /freeze <player>");
            return;
        }
        $player = Server::getInstance()->getPlayerExact($args[0]);
        $session = SessionFactory::getSession($player);
        if ($player === null) {
            $sender->sendMessage(TextFormat::RED . "That player is not online.");
            return;
        }
        if ($session->isFrozen()) {
            $session->setFrozen(false);
            $sender->sendMessage(TextFormat::GREEN . "You have unfrozen " . $args[0]);
            return;
        }
        $session->setFrozen();
        $sender->sendMessage(TextFormat::GREEN . "You have frozen " . $args[0]);
    }
}
