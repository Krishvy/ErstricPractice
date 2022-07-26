<?php

declare(strict_types=1);


namespace Krishvy\session;

use Krishvy\Erstric;
use Krishvy\queue\duel\Duel;
use Krishvy\queue\Queue;
use Krishvy\utils\Utils;
use pocketmine\block\VanillaBlocks;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\VanillaEnchantments;
use pocketmine\item\PotionType;
use pocketmine\item\VanillaItems;
use pocketmine\network\mcpe\protocol\RemoveObjectivePacket;
use pocketmine\network\mcpe\protocol\SetDisplayObjectivePacket;
use pocketmine\network\mcpe\protocol\SetScorePacket;
use pocketmine\network\mcpe\protocol\types\ScorePacketEntry;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

class Session
{

    private Player $player;
    private Config $data;
    private string|null $kit = null;
    private string $lastReplied = "";
    private bool $frozen = false;
    private ?Duel $duel = null;
    private int $hits = 0;
    private string $scoreboardType = "hub";
    private array $line = [];
    private float $duelTime = 0;

    public function __construct(Player $player)
    {
        $this->player = $player;
        if (!file_exists(Erstric::getInstance()->getDataFolder() . "sessions/")) {
            mkdir(Erstric::getInstance()->getDataFolder() . "sessions/");
        }
        $this->data = new Config(Erstric::getInstance()->getDataFolder() . "sessions/{$player->getName()}.json", Config::JSON, array(
            "settings" => array(
                "autosprint" => "false",
                "lightningdeath" => "false",
                "privatemessages" => "false",
                "bloodkill" => "false",
            ),
            "stats" => array(
                "kills" => 0,
                "deaths" => 0,
                "wins" => 0,
                "losses" => 0,
                "killstreak" => 0,
                "deathstreak" => 0,
                "elo" => 1000,
                "coins" => 0,
            )
        ));
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }

    public function setFrozen(bool $value = true): void
    {
        $this->frozen = $value;
    }

    public function isFrozen(): bool
    {
        return $this->frozen;
    }

    public function setKit(string $kit = null): void
    {
        $this->kit = $kit;
        if (is_null($kit)) {
            $this->kit = null;
        }
    }

    public function getKit(): string
    {
        return $this->kit;
    }

    public function getSettings(): array
    {
        return $this->data->getNested('settings');
    }

    public function setSetting(string $setting, bool|int|string $value = false): void
    {
        $this->data->setNested('settings.' . $setting, $value);
        $this->data->save();
    }

    public function getStatistics(): array
    {
        return $this->data->getNested('stats');
    }

    public function setStatistics(string $statistic, int $value): void
    {
        $this->data->setNested('stats.' . $statistic, $value);
        $this->data->save();
    }

    public function getWLR(): float
    {
        $wins = $this->getStatistics()['wins'];
        $losses = $this->getStatistics()['losses'];
        return $wins / $losses;
    }

    public function getKDR(): float
    {
        $kills = $this->getStatistics()['kills'];
        $deaths = $this->getStatistics()['deaths'];
        return $kills / $deaths;
    }



    public function clear(): void
    {
        $this->player->setHealth(20);
        $this->player->getXpManager()->setXpAndProgress(0, 0);
        $this->player->getInventory()->clearAll();
        $this->player->getArmorInventory()->clearAll();
        $this->player->getCursorInventory()->clearAll();
        $this->player->getArmorInventory()->clearAll();
        $this->setKit();
        $this->player->getEffects()->clear();
    }

    public function teleportToLobby(): void
    {
        $this->player->teleport(Server::getInstance()->getWorldManager()->getWorldByName('lobby')->getSafeSpawn());
    }

    public function setLastReplied(string $name): void
    {
        $this->lastReplied = $name;
    }

    public function getLastReplied(): string
    {
        return $this->lastReplied;
    }

    public function lobbyItems(): void
    {
        $inventory = $this->player->getInventory();
        $this->clear();
        $this->player->setGamemode(GameMode::ADVENTURE());
        $inventory->setItem(0, VanillaItems::IRON_SWORD()->setCustomName(Utils::color("{LIGHT_PURPLE}FFA{GRAY} (Right Click)")));
        $inventory->setItem(1, VanillaItems::DIAMOND_SWORD()->setCustomName(Utils::color("{LIGHT_PURPLE}Duel{GRAY} (Right Click)")));
        $inventory->setItem(2, VanillaItems::BOOK()->setCustomName(Utils::color("{LIGHT_PURPLE}Edit Kits{GRAY} (Right Click)")));
        $inventory->setItem(4, VanillaBlocks::MOB_HEAD()->asItem()->setCustomName(Utils::color("{LIGHT_PURPLE}Your Stats{GRAY} (Right Click)")));
        $inventory->setItem(7, VanillaItems::NETHER_STAR()->setCustomName(Utils::color("{LIGHT_PURPLE}Spectate{GRAY} (Right Click)")));
        $inventory->setItem(8, VanillaBlocks::ANVIL()->asItem()->setCustomName(Utils::color("{LIGHT_PURPLE}Settings{GRAY} (Right Click)")));
        $this->player->getEffects()->add(new EffectInstance(VanillaEffects::NIGHT_VISION(), 2147483647, 1, false));
    }

    public function diamondArmor(): void
    {
        $enchant = new EnchantmentInstance(VanillaEnchantments::UNBREAKING(), 3);
        $this->player->getArmorInventory()->setBoots(VanillaItems::DIAMOND_BOOTS()->addEnchantment($enchant));
        $this->player->getArmorInventory()->setHelmet(VanillaItems::DIAMOND_HELMET()->addEnchantment($enchant));
        $this->player->getArmorInventory()->setChestplate(VanillaItems::DIAMOND_CHESTPLATE()->addEnchantment($enchant));
        $this->player->getArmorInventory()->setLeggings(VanillaItems::DIAMOND_LEGGINGS()->addEnchantment($enchant));
    }

    public function kit(string $kit): void
    {
        $this->player->getEffects()->add(new EffectInstance(VanillaEffects::NIGHT_VISION(), 214748364, 1, false));
        switch ($kit) {
            case "dragon":
                $inventory = $this->player->getInventory();
                SessionFactory::getSession($this->player)->clear();
                $this->player->getEffects()->add(new EffectInstance(VanillaEffects::SPEED(), Utils::secondsToTicks(999999), 0, false));
                $item = VanillaItems::DIAMOND_SWORD()->addEnchantment(new EnchantmentInstance(VanillaEnchantments::UNBREAKING(), 3));
                $inventory->setItem(0, $item);
                $item1 = VanillaItems::ENDER_PEARL()->setCount(16);
                $inventory->setItem(2, $item1);
                $item2 = VanillaItems::ENCHANTED_GOLDEN_APPLE()->setCount(64);
                $inventory->setItem(1, $item2);
                $this->player->getEffects()->add(new EffectInstance(VanillaEffects::STRENGTH(), 99999 * 14, 0));
                $this->player->getEffects()->add(new EffectInstance(VanillaEffects::RESISTANCE(), 99999 * 14, 0));
                $this->player->getEffects()->add(new EffectInstance(VanillaEffects::NIGHT_VISION(), 99999 * 14, 0));
                $this->player->getEffects()->add(new EffectInstance(VanillaEffects::HEALTH_BOOST(), 99999 * 14, 2));
                $this->player->getEffects()->add(new EffectInstance(VanillaEffects::SPEED(), 99999 * 14, 2));
                $this->diamondArmor();
                $this->player->setGamemode(GameMode::ADVENTURE());
                break;
            case "nodebuff":
                    $player = $this->player;
                    $inventory = $player->getInventory();
                    SessionFactory::getSession($player)->clear();
                    $player->getEffects()->add(new EffectInstance(VanillaEffects::SPEED(), Utils::secondsToTicks(999999), 0, false));
                    $item = VanillaItems::DIAMOND_SWORD()->addEnchantment(new EnchantmentInstance(VanillaEnchantments::UNBREAKING(), 3));
                    $inventory->setItem(0, $item);
                    $item1 = VanillaItems::ENDER_PEARL()->setCount(16);
                    $inventory->setItem(1, $item1);
                    $item2 = VanillaItems::SPLASH_POTION()->setType(PotionType::STRONG_HEALING())->setCount(34);
                    $inventory->addItem($item2);
                    $this->diamondArmor();
                    $player->setGamemode(GameMode::ADVENTURE());
                break;
            case "sumo":
                SessionFactory::getSession($this->player)->clear();
                $this->player->getEffects()->add(new EffectInstance(VanillaEffects::RESISTANCE(), 9977, 255, false));
                $this->player->getInventory()->addItem(VanillaItems::STEAK()->setCount(64));
                $this->player->setGamemode(GameMode::ADVENTURE());
                break;
            case "build":
                $player = $this->player;
                $this->clear();
                $enchant = new EnchantmentInstance(VanillaEnchantments::UNBREAKING(), 3);
                $player->getArmorInventory()->setBoots(VanillaItems::IRON_BOOTS()->addEnchantment($enchant)->setUnbreakable());
                $player->getArmorInventory()->setLeggings(VanillaItems::GOLDEN_LEGGINGS()->addEnchantment($enchant)->setUnbreakable());
                $player->getArmorInventory()->setChestplate(VanillaItems::GOLDEN_CHESTPLATE()->addEnchantment($enchant)->setUnbreakable());
                $player->getArmorInventory()->setHelmet(VanillaItems::IRON_HELMET()->addEnchantment($enchant)->setUnbreakable());
                $player->getInventory()->setItem(0, VanillaItems::GOLDEN_SWORD()->setUnbreakable()->addEnchantment($enchant));
                $player->getInventory()->setItem(1, VanillaBlocks::CONCRETE()->asItem()->setCount(64));
                $player->getInventory()->setItem(2, VanillaItems::ENDER_PEARL()->setCount(2));
                $player->getInventory()->setItem(3, VanillaItems::GOLDEN_APPLE()->setCount(3));
                $player->getInventory()->setItem(4, VanillaItems::IRON_PICKAXE()->setUnbreakable());
                $this->player->setGamemode(GameMode::SURVIVAL());
                break;
            case "oitc":
                $player = $this->player;
                $this->clear();
                $enchant = new EnchantmentInstance(VanillaEnchantments::UNBREAKING(), 3);
                $player->getInventory()->setItem(0, VanillaItems::STONE_SWORD()->setUnbreakable()->addEnchantment($enchant));
                $player->getInventory()->setItem(8, VanillaItems::ARROW());
                $bow = VanillaItems::BOW();
                $bow->addEnchantment($enchant);
                $bow->addEnchantment(new EnchantmentInstance(VanillaEnchantments::INFINITY()));
                $bow->addEnchantment(new EnchantmentInstance(VanillaEnchantments::POWER(), 3));
                $player->getInventory()->setItem(1, $bow);
                $player->setGamemode(GameMode::ADVENTURE());
                break;
            case "boxing":
                $player = $this->player;
                $this->clear();
                $player->getInventory()->addItem(VanillaItems::DIAMOND_SWORD());
                $player->getEffects()->add(new EffectInstance(VanillaEffects::RESISTANCE(), 921348014, 255, false));
                $player->setGamemode(GameMode::ADVENTURE());
                break;
        }
    }

    public function setInDuel(?Duel $duel): void
    {
        if(is_null($duel)){
            $this->scoreboardType = 'hub';
        } else {
            $this->duel = $duel;
            $this->scoreboardType = 'duel';
            if ($duel->getKit() === 'boxing') {
                $this->scoreboardType = 'boxing';
            }
        }
    }

    public function getInDuel(): ?Duel
    {
        return $this->duel;
    }

    public function getDuelOpponent() : Player {
        return $this->duel->getPlayers()[0]->getName() == $this->player->getName() ? $this->duel->getPlayers()[1] : $this->duel->getPlayers()[0];
    }

    public function addHit(): void
    {
        $this->hits++;
    }

    public function getHits(): int
    {
        return $this->hits;
    }

    public function setHits(int $hits): void
    {
        $this->hits = $hits;
    }

    public function updateScoreboard(): void
    {
        $player = $this->player;
        $this->showScoreboard($player);
        $this->clearLines($player);
        $time = $this->duelTime - microtime(true);
        switch ($this->scoreboardType) {
            case "hub":
                $this->addLine("§l§7⎻⎻⎻⎻⎻⎻⎻⎻⎻⎻⎻⎻⎻⎻⎻⎻⎻⎻⎻§f§r", $player);
                $this->addLine("§bOnline: §f" . count(Server::getInstance()->getOnlinePlayers()), $player);
                $this->addLine("§bIn Queue: §f" . Queue::getEveryoneInQueues(), $player);
                $this->addLine("§r§c    §f", $player);
                $this->addLine("§bskyetri.tk§r", $player);
                $this->addLine("§l§7⎻⎻⎻⎻⎻⎻⎻⎻⎻⎻⎻⎻⎻⎻⎻⎻⎻⎻⎻§r", $player);
                break;
            case "duel":
                $this->addLine("§l§7⎻⎻⎻⎻⎻⎻⎻⎻⎻⎻⎻⎻⎻⎻⎻⎻⎻⎻⎻§f§r", $player);
                $this->addLine("§fDuration: §b".date("h:i:s", $time), $player);
                $this->addLine("§fOpponent: §b".$this->getDuelOpponent()->getName(), $player);
                $this->addLine("§r§3    §f", $player);
                $this->addLine("§fYour Ping: §b" . $player->getNetworkSession()->getPing(), $player);
                $this->addLine("§fThier Ping: §b" . $this->getDuelOpponent()->getNetworkSession()->getPing(), $player);
                $this->addLine("§r§b    §f", $player);
                $this->addLine("§bskyetri.tk§r", $player);
                $this->addLine("§l§7⎻⎻⎻⎻⎻⎻⎻⎻⎻⎻⎻⎻⎻⎻⎻⎻⎻⎻⎻§r", $player);
                break;
            case "boxing":
                $opponentHits = SessionFactory::getSession($this->getDuelOpponent())->getHits();
                $playerHits = SessionFactory::getSession($player)->getHits();
                $this->addLine("§l§7⎻⎻⎻⎻⎻⎻⎻⎻⎻⎻⎻⎻⎻⎻⎻⎻⎻⎻⎻§f§r", $player);
                $this->addLine("§fDuration: 1:04", $player);
                $this->addLine("§fOpponent: §b".$this->getDuelOpponent()->getName(), $player);
                $this->addLine("§r§3    §f", $player);
                $this->addLine('§fYour Hits: §b' . $playerHits, $player);
                $this->addLine('§fTheir Hits: §b' . $opponentHits, $player);
                $this->addLine("§8    §f", $player);
                $this->addLine("§fYour Ping: §b" . $player->getNetworkSession()->getPing(), $player);
                $this->addLine("§fThier Ping: §b" . $this->getDuelOpponent()->getNetworkSession()->getPing(), $player);
                $this->addLine("§r§b    §f", $player);
                $this->addLine("§bskyetri.tk§r", $player);
                $this->addLine("§l§7⎻⎻⎻⎻⎻⎻⎻⎻⎻⎻⎻⎻⎻⎻⎻⎻⎻⎻⎻§r", $player);
                break;
        }
    }

    public function showScoreboard(Player $player): void
    {
        $pk = new SetDisplayObjectivePacket();
        $pk->displaySlot = "sidebar";
        $pk->objectiveName = $player->getName();
        $pk->displayName = "§l§bSky§fetri§r";
        $pk->criteriaName = "dummy";
        $pk->sortOrder = 0;
        $player->getNetworkSession()->sendDataPacket($pk);
    }

    public function addLine(string $line, Player $player): void
    {
        $score = count($this->line) + 1;
        $this->setLine($score, $line, $player);
    }

    public function removeScoreboard(Player $player): void
    {
        $objectiveName = $player->getName();
        $pk = new RemoveObjectivePacket();
        $pk->objectiveName = $objectiveName;
        $player->getNetworkSession()->sendDataPacket($pk);
    }

    public function clearLines(Player $player): void
    {
        for ($line = 0; $line <= 15; $line++) {
            $this->removeLine($line, $player);
        }
    }

    public function setLine(int $loc, string $msg, Player $player): void
    {
        $pk = new ScorePacketEntry();
        $pk->objectiveName = $player->getName();
        $pk->type = $pk::TYPE_FAKE_PLAYER;
        $pk->customName = $msg;
        $pk->score = $loc;
        $pk->scoreboardId = $loc;
        if (isset($this->line[$loc])) {
            unset($this->line[$loc]);
            $pkt = new SetScorePacket();
            $pkt->type = $pkt::TYPE_REMOVE;
            $pkt->entries[] = $pk;
            $player->getNetworkSession()->sendDataPacket($pkt);
        }
        $pkt = new SetScorePacket();
        $pkt->type = $pkt::TYPE_CHANGE;
        $pkt->entries[] = $pk;
        $player->getNetworkSession()->sendDataPacket($pkt);
        $this->line[$loc] = $msg;
    }

    public function removeLine(int $line, Player $player): void
    {
        $pk = new SetScorePacket();
        $pk->type = $pk::TYPE_REMOVE;
        $entry = new ScorePacketEntry();
        $entry->objectiveName = $player->getName();
        $entry->score = $line;
        $entry->scoreboardId = $line;
        $pk->entries[] = $entry;
        $player->getNetworkSession()->sendDataPacket($pk);
        if (isset($this->line[$line])) {
            unset($this->line[$line]);
        }
    }

}