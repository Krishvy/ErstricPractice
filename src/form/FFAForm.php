<?php

declare(strict_types=1);

namespace Krishvy\form;

use Krishvy\libs\form\SimpleForm;
use Krishvy\session\SessionFactory;
use Krishvy\utils\Utils;
use pocketmine\entity\Location;
use pocketmine\player\Player;
use pocketmine\Server;

class FFAForm
{
    static function send(Player $player): void
    {
        $form = new SimpleForm(function (Player $player, $data = null) {
            if ($data === null) {
                return true;
            }
            switch ($data) {
                case "nodebuff":
                    SessionFactory::getSession($player)->kit("nodebuff");
                    $name = Server::getInstance()->getWorldManager()->getWorldByName($data);
                    switch (mt_rand(0, 3)) {
                        case 0:
                            $player->teleport(new Location(280, 65, 294, $name, 0, 0));
                            break;
                        case 1:
                            $player->teleport(new Location(318, 65, 273, $name, 0, 0));
                            break;
                        case 2:
                            $player->teleport(new Location(351, 65, 307, $name, 0, 0));
                            break;
                        case 3:
                            $player->teleport(new Location(326, 65, 322, $name, 0, 0));
                            break;
                    }
                    break;
                case "sumo":
                    $name = Server::getInstance()->getWorldManager()->getWorldByName($data);
                    switch (mt_rand(0, 3)) {
                        case 0:
                            $player->teleport(new Location(292, 65, 256, $name, 0, 0));
                            break;
                        case 1:
                            $player->teleport(new Location(284, 67, 227, $name, 0, 0));
                            break;
                        case 2:
                            $player->teleport(new Location(268, 65, 258, $name, 0, 0));
                            break;
                        case 3:
                            $player->teleport(new Location(236, 65, 276, $name, 0, 0));
                            break;
                    }
                    SessionFactory::getSession($player)->kit("sumo");
                    break;
                case "oitc":
                    SessionFactory::getSession($player)->kit("oitc");
                    break;
                case "dragon":
                    $name = Server::getInstance()->getWorldManager()->getWorldByName($data);
                    switch (mt_rand(0, 3)) {
                        case 0:
                            $player->teleport(new Location(229, 65, 230, $name, 0, 0));
                            break;
                        case 1:
                            $player->teleport(new Location(212, 65, 206, $name, 0, 0));
                            break;
                        case 2:
                            $player->teleport(new Location(248, 65, 176, $name, 0, 0));
                            break;
                        case 3:
                            $player->teleport(new Location(246, 65, 246, $name, 0, 0));
                            break;
                    }
                    SessionFactory::getSession($player)->kit("dragon");
                    break;
                case "build":
                    SessionFactory::getSession($player)->kit("build");
                    $name = Server::getInstance()->getWorldManager()->getWorldByName($data);
                    switch (mt_rand(0, 3)) {
                        case 0:
                            $player->teleport(new Location(1, 9, -74, $name, 0, 0));
                            break;
                        case 1:
                            $player->teleport(new Location(70, 10, -29, $name, 0, 0));
                            break;
                        case 2:
                            $player->teleport(new Location(-6, 10, 12, $name, 0, 0));
                            break;
                        case 3:
                            $player->teleport(new Location(-74, 10, -27, $name, 0, 0));
                            break;
                    }
                    break;
            }
            return true;
        });
        $form->setTitle(Utils::color("{GRAY}FFA"));
        $form->addButton(Utils::color("{WHITE}NoDebuff\n{GRAY}Playing: {WHITE}" . count(Server::getInstance()->getWorldManager()->getWorldByName("nodebuff")->getPlayers())), 0, "textures/items/potion_bottle_splash_heal", "nodebuff");     $form->addButton(Utils::color("{WHITE}Freeshop\n{GRAY}Playing: {WHITE}" . count(Server::getInstance()->getWorldManager()->getWorldByName("freeshop")->getPlayers())), 0, "textures/items/snowball", "freeshop");
        $form->addButton(Utils::color("{WHITE}Dragon\n{GRAY}Playing: {WHITE}" . count(Server::getInstance()->getWorldManager()->getWorldByName("dragon")->getPlayers())), 0, "textures/items/ender_eye", "dragon");
        $form->addButton(Utils::color("{WHITE}Sumo\n{GRAY}Playing: {WHITE}" . count(Server::getInstance()->getWorldManager()->getWorldByName("sumo")->getPlayers())), 0, "textures/items/lead", "sumo");
        $form->addButton(Utils::color("{WHITE}Build\n{GRAY}Playing: {WHITE}" . count(Server::getInstance()->getWorldManager()->getWorldByName("build")->getPlayers())), 0, "textures/blocks/wool_colored_white", "build");
         $form->addButton(Utils::color("{WHITE}OITC\n{GRAY}Playing: {WHITE}" . count(Server::getInstance()->getWorldManager()->getWorldByName("oitc")->getPlayers())), 0, "textures/items/bow_standby", "oitc");
        $player->sendForm($form);

    }
}