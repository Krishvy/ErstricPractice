<?php

declare(strict_types=1);

namespace Krishvy\form;

use Krishvy\libs\form\SimpleForm;
use Krishvy\queue\Queue;
use Krishvy\utils\Utils;
use pocketmine\player\Player;
use pocketmine\Server;

class DuelsForm
{
    static function send(Player $player): void
    {
        $form = new SimpleForm(function (Player $player, $data = null) {
            if($data === null) return;
            Queue::placeInQueue($player, $data);
        });
        $form->setTitle(Utils::color("{GRAY}Duels"));
        $form->addButton(Utils::color("{WHITE}NoDebuff\n{GRAY}Queue: {WHITE}" . Queue::getQueueCount('nodebuff')), 0, "textures/items/potion_bottle_splash_heal", "nodebuff");
        $form->addButton(Utils::color("{WHITE}Boxing\n{GRAY}Queue: {WHITE}" . Queue::getQueueCount('boxing')), 0, "textures/items/diamond_chestplate", "boxing");
        $form->addButton(Utils::color("{WHITE}Freeshop\n{GRAY}Queue: {WHITE}" . Queue::getQueueCount('freeshop')), 0, "textures/items/snowball", "freeshop");
        $form->addButton(Utils::color("{WHITE}Dragon\n{GRAY}Queue: {WHITE}" . Queue::getQueueCount('dragon')), 0, "textures/items/ender_eye", "dragon");
        $player->sendForm($form);

    }
}