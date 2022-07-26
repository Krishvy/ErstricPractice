<?php

declare(strict_types=1);

namespace Krishvy\form;

use Krishvy\libs\form\SimpleForm;
use Krishvy\session\SessionFactory;
use Krishvy\utils\Utils;
use pocketmine\player\Player;

class StatisticsForm
{
    static function send(Player $player): void
    {
        $form = new SimpleForm(function (Player $player, $data = null){
            if($data === null) return;
        });
        $form->setTitle('Statistics');
        $session = SessionFactory::getSession($player);
        $kills = $session->getStatistics()['kills'];
        $deaths = $session->getStatistics()['deaths'];
        $wins = $session->getStatistics()['wins'];
        $loss = $session->getStatistics()['losses'];
        $kdr = null;
        if($kills === 0 && $deaths === 0) {
            $kdr = "{AQUA}KDR: {WHITE}N/A";
                } else {
            "{AQUA}KDR: {WHITE}" . $session->getKDR();
            }
        $wlr = null;
        if($wins === 0 && $loss === 0){
            $wlr = "{AQUA}WLR: {WHITE}N/A";
        } else {
            $wlr = "{AQUA}WLR: {WHITE}" . $session->getWLR();
        }
        $join = [
          "{GRAY}".str_repeat("-", 20),
          "{AQUA}Kills: {WHITE}".$session->getStatistics()['kills'],
            "{AQUA}Deaths: {WHITE}".$session->getStatistics()['deaths'],
            $kdr,
            "{AQUA}Killstreak: {WHITE}".$session->getStatistics()['killstreak'],
            "{AQUA}Deathstreak: {WHITE}".$session->getStatistics()['deathstreak'],
            "{GRAY}".str_repeat("-", 20),
            "{AQUA}Wins: {WHITE}".$session->getStatistics()['wins'],
            "{AQUA}Losses: {WHITE}".$session->getStatistics()['losses'],
            $wlr,
            "{GRAY}".str_repeat("-", 20),
        ];
        $form->setContent(Utils::color(join("\n", $join)));
        $player->sendForm($form);
    }
}