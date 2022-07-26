<?php

declare(strict_types=1);

namespace Krishvy;

use Krishvy\form\DuelsForm;
use Krishvy\form\FFAForm;
use Krishvy\form\StatisticsForm;
use Krishvy\queue\Queue;
use Krishvy\session\SessionFactory;
use Krishvy\utils\Utils;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\event\block\{BlockBreakEvent, BlockBurnEvent, BlockPlaceEvent};
use pocketmine\entity\projectile\SplashPotion;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\ProjectileHitBlockEvent;
use pocketmine\event\inventory\CraftItemEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\{PlayerChatEvent,
    PlayerDropItemEvent,
    PlayerExhaustEvent,
    PlayerItemUseEvent,
    PlayerJoinEvent,
    PlayerLoginEvent,
    PlayerMoveEvent,
    PlayerQuitEvent};
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\item\PotionType;
use pocketmine\item\VanillaItems;
use pocketmine\network\mcpe\protocol\{AnimatePacket, PlayerAuthInputPacket};
use pocketmine\network\mcpe\protocol\types\PlayerAuthInputFlags;
use pocketmine\player\Player;
use pocketmine\Server;

class Events implements Listener
{

    public function onPlayerLogin(PlayerLoginEvent $ev): void
    {
        SessionFactory::startSession($ev->getPlayer());
    }

    public function onPlayerJoin(PlayerJoinEvent $ev): void
    {
        $player = $ev->getPlayer();
        SessionFactory::getSession($player)->clear();
        $ev->setJoinMessage(Utils::color("{GRAY}[{GREEN}+{GRAY}]{WHITE} {$player->getName()}"));
        SessionFactory::getSession($player)->lobbyItems();
        Utils::teleportToLobby($player);
        Utils::changelogMessage($player);
    }

    public function onPlayerQuit(PlayerQuitEvent $ev): void
    {
        if(SessionFactory::getSession($ev->getPlayer())->getInDuel()){
            SessionFactory::getSession($ev->getPlayer())->getInDuel()->win(SessionFactory::getSession($ev->getPlayer())->getDuelOpponent(), $ev->getPlayer());
        }
        Queue::removeFromQueue($ev->getPlayer(), false);
        $ev->setQuitMessage(Utils::color("{GRAY}[{RED}-{GRAY}]{WHITE} {$ev->getPlayer()->getName()}"));
    }

    public function onDataPacketReceive(DataPacketReceiveEvent $ev): void
    {
        $player = $ev->getOrigin()->getPlayer();
        if (!$player instanceof Player) return;
        $session = SessionFactory::getSession($player);
        $packet = $ev->getPacket();
        switch ($packet->pid()) {
            case PlayerAuthInputPacket::NETWORK_ID:
                /** @var PlayerAuthInputPacket $packet */
                if ($session->getSettings()["autosprint"]) {
                    if ($player->isSprinting() && $packet->hasFlag(PlayerAuthInputFlags::DOWN)) {
                        $player->setSprinting(false);
                    } elseif (!$player->isSprinting() && $packet->hasFlag(PlayerAuthInputFlags::UP)) {
                        $player->setSprinting();
                    }
                }
                break;
            case AnimatePacket::NETWORK_ID:
                /** @var AnimatePacket $packet */
                if ($packet->action === AnimatePacket::ACTION_SWING_ARM) {
                    $ev->cancel();
                    $player->getServer()->broadcastPackets($player->getViewers(), [$packet]);
                }
                break;
        }
    }

    public function onPlayerChat(PlayerChatEvent $ev): void
    {
    }

    /**
     * @param PlayerItemUseEvent $ev
     * @handleCancelled
     */
    public function onPlayerItemUse(PlayerItemUseEvent $ev): void
    {
        $player = $ev->getPlayer();
        if ($ev->getPlayer()->getWorld()->getFolderName() === "lobby") {
            $ev->cancel();
            switch($ev->getItem()->getTypeId()){
                case VanillaItems::DIAMOND_SWORD()->getTypeId():
                    DuelsForm::send($player);
                    break;
                case VanillaItems::IRON_SWORD()->getTypeId():
                    FFAForm::send($player);
                    break;
                case VanillaBlocks::MOB_HEAD()->asItem()->getTypeId():
                    StatisticsForm::send($player);
                    break;
                case VanillaItems::NETHER_STAR()->getTypeId():
                case VanillaItems::BOOK()->getTypeId():
                    $player->sendActionBarMessage('Coming soon');
                    break;
                case VanillaItems::DYE()->setColor(DyeColor::RED())->getTypeId():
                    Queue::removeFromQueue($player);
                    SessionFactory::getSession($player)->lobbyItems();
            }
        }
    }

    public function onPlayerItemDrop(PlayerDropItemEvent $ev): void
    {
        $ev->cancel();
    }

    public function onExhaust(PlayerExhaustEvent $ev): void
    {
        $ev->cancel();
    }

    public function onCraft(CraftItemEvent $ev): void
    {
        $ev->cancel();
    }

    public function onBlockPlace(BlockPlaceEvent $ev): void
    {
    }

    public function onBlockBreak(BlockBreakEvent $ev): void
    {
    }

    public function onFireSpread(BlockBurnEvent $ev): void
    {
        $ev->cancel();
    }

     public function onPlayerMove(PlayerMoveEvent $ev): void
     {
           if(SessionFactory::getSession($ev->getPlayer())->isFrozen()) {
               $ev->cancel();
           }
     }

    public function onProjectileHit(ProjectileHitBlockEvent $event)
    {
        $projectile = $event->getEntity();
        if ($projectile instanceof SplashPotion && $projectile->getPotionType() === PotionType::STRONG_HEALING()) {
            $player = $projectile->getOwningEntity();
            if ($player instanceof Player && $player->isAlive() && $projectile->getPosition()->distance($player->getPosition()) <= 4) {
                $player->setHealth($player->getHealth() + 5);
            }
        }
    }

    public function onPlayerHit(EntityDamageByEntityEvent $event)
    {
        $player = $event->getEntity();
        $world = $player->getWorld()->getFolderName();
        $killer = $event->getDamager();
        $dsession = SessionFactory::getSession($player);
        if ($killer instanceof Player && $player instanceof Player) {
            $ksession = SessionFactory::getSession($killer);
       /* if($world === "lobby"){
            $event->cancel();
        }*/
            if ($player->isAlive()) {
                $playerHealth = $player->getHealth();
                $finalDamage = $event->getFinalDamage();
                if ($finalDamage >= $playerHealth) {
                    if(!$ksession->getInDuel() && !$dsession->getInDuel()) {
                        $dsession->teleportToLobby();
                        $dsession->clear();
                        $dsession->lobbyItems();
                        $ksession->setStatistics('kills', $ksession->getStatistics()['kills'] + 1);
                        $dsession->setStatistics('deaths', $dsession->getStatistics()['deaths'] + 1);
                        $deathmsg = ["§c{$player->getName()}§7was killed by §6{$killer->getName()}", "§a{$killer->getName()}§7was the better player against §c{$player->getName()}", "§c{$player->getName()}§7was knocked out by §a{$killer->getName()}", "§c{$player->getName()}§7was sent to space by §a{$killer->getName()}", "§c{$player->getName()}§7was taken out by §a{$killer->getName()}", "§c{$player->getName()}§7was sent to heaven by §a{$killer->getName()}", "§a{$killer->getName()}§7sent §c{$player->getName()}§7to spawn!", "§c{$player->getName()}§7was split open by §a{$killer->getName()}"];
                        Server::getInstance()->broadcastMessage($deathmsg[array_rand($deathmsg)]);
                    } else {
                        switch($ksession->getInDuel()->getKit()){
                            case "boxing":
                                break;
                            default:
                                $ksession->getInDuel()->win($killer, $player);
                                break;
                        }
                    }
                }
            }
        }
    }

    public function boxing(EntityDamageByEntityEvent $ev): void
    {
        $player = $ev->getEntity();
        $damager = $ev->getDamager();
        if($damager instanceof Player && $player instanceof Player){
            $dsession = SessionFactory::getSession($player);
            $ksession = SessionFactory::getSession($damager);
            if($dsession->getInDuel()){
                if($dsession->getInDuel()->getKit() === "boxing"){
                    $ksession->addHit();
                    $ksession->updateScoreboard();
                    $dsession->updateScoreboard();
                    if($ksession->getHits() === 100){
                        $ksession->setHits(0);
                        $ksession->getInDuel()->win($damager, $player);
                    }
                }
            }
        }
    }

}
