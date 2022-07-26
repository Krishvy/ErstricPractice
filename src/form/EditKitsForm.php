<?php

declare(strict_types=1);

namespace Krishvy\form;

use pocketmine\player\Player;

class EditKitsForm
{
    static function send(Player $player)
    {
        /*     $form = new SimpleForm(function (Player $player, $data = null) {
                 if ($data === null) return;
                 $player->sendMessage(Utils::color("{GRAY}Chat {GREEN}Confirm{GRAY} to save your kit\n{GRAY}Chat {RED}Cancel{GRAY} to keep your kit as it is."));
                 SessionFactory::getSession($player)->setEditingKits(true);
                 SessionFactory::getSession($player)->setKit($data);
                 switch ($data) {
                     case "nodebuff":
                         SessionFactory::getSession($player)->kit("nodebuff");
                         break;
                     case "freeshop":
                         SessionFactory::getSession($player)->kit("freeshop");
                         break;
                     case "dragon":
                         SessionFactory::getSession($player)->kit("dragon");
                         break;
                     case "sumo":
                         SessionFactory::getSession($player)->kit("sumo");
                         break;
                     case "build":
                         SessionFactory::getSession($player)->kit("build");
                         break;
                     case "oitc":
                         SessionFactory::getSession($player)->kit("oitc");
                         break;
                 }
             });
             $form->setTitle(Utils::color("{GRAY}Edit Kits"));
             $form->addButton(Utils::color("{WHITE}NoDebuff"), 0, "textures/items/potion_bottle_splash_heal", "nodebuff");
             $form->addButton(Utils::color("{WHITE}Freeshop"), 0, "textures/items/snowball", "freeshop");
             $form->addButton(Utils::color("{WHITE}Dragon"), 0, "textures/items/ender_eye", "dragon");
             $form->addButton(Utils::color("{WHITE}Sumo"), 0, "textures/items/lead", "sumo");
             $form->addButton(Utils::color("{WHITE}Build"), 0, "textures/blocks/wool_colored_white", "build");
             $form->addButton(Utils::color("{WHITE}OITC"), 0, "textures/items/bow_standby", "oitc");
             $player->sendForm($form); */
    }
}