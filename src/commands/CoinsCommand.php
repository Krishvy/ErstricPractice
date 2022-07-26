<?php

declare(strict_types=1);

namespace Krishvy\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class CoinsCommand extends Command
{
    public function __construct()
    {
        parent::__construct("coins", "Check how many coins you have or pay other people coins.", "/coins <pay>");
    }
    public function execute(CommandSender $sender, string $commandLabel, array $args): void
    {
        if(!$sender instanceof Player){
            return;
        }
//todo
        switch($args[0]){
            case 'pay':
                break;
            case 'set':
                break;
            case 'add':
                break;
            default:
                $sender->sendMessage(TextFormat::RED.$this->getUsage());
                break;
        }

    }
}