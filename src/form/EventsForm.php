<?php

declare(strict_types=1);

namespace Krishvy\form;

use Krishvy\event\EventHandler;
use Krishvy\event\EventGame;
use Krishvy\libs\form\SimpleForm;
use Krishvy\utils\Utils;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class EventsForm
{
    static function send(Player $player): void
    {
        $form = new SimpleForm(function (Player $player, $data = null) {
            switch($data)
            {
                case 0:
                    self::joinEvent($player);
                    break;
                case 1:
                    self::createEvent($player);
                    break;
            }
        });
        $form->setTitle("Events");
        $form->addButton('Join Event');
        $form->addButton('Create Event');

    }

    static function createEvent(Player $player): void
    {
        $form = new SimpleForm(function (Player $player, $data = null) {
        });
        $form->setTitle("Events");
    }

    static function joinEvent(Player $player): void
    {
        $form = new SimpleForm(function (Player $player, $data = null) {
            if ($data === null) {
                return true;
            }
                    if(EventHandler::$isEvent){
                        EventGame::addToEvent($player);
                    }
                    if(EventHandler::$isEventRunning){
                        $player->sendMessage(TextFormat::RED."That event has already started.");
                    }
            return true;
        });
        $form->setTitle(Utils::color("{GRAY}EVENTS"));
        $form->addButton(Utils::color("{WHITE}Sumo"), 0, "textures/items/lead", "sumo");
        $player->sendForm($form);
    }
}