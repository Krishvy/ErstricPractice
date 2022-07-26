<?php

declare(strict_types=1);

namespace Krishvy\utils;

use DateTime;
use FilesystemIterator;
use Krishvy\Erstric;
use Krishvy\libs\CortexPE\DiscordWebhookAPI\Embed;
use Krishvy\libs\CortexPE\DiscordWebhookAPI\Message;
use Krishvy\libs\CortexPE\DiscordWebhookAPI\Webhook;
use pocketmine\network\mcpe\protocol\types\DeviceOS;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\Server;
use pocketmine\utils\AssumptionFailedError;
use pocketmine\utils\TextFormat;
use pocketmine\world\format\io\data\BaseNbtWorldData;
use pocketmine\world\World;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class Utils
{
    const CHAT_LOGS = 'https://discord.com/api/webhooks/991631784582324224/MdApCn63OL7L6f6dSHs65bW7nEBKuCk5XhbaplAclJmEID1GuXE0M7N9DfgraobSwjet';
    const BAN_LOGS = 'https://discord.com/api/webhooks/991631784582324224/MdApCn63OL7L6f6dSHs65bW7nEBKuCk5XhbaplAclJmEID1GuXE0M7N9DfgraobSwjet';
    const CONNECT_LOGS = 'https://discord.com/api/webhooks/991631784582324224/MdApCn63OL7L6f6dSHs65bW7nEBKuCk5XhbaplAclJmEID1GuXE0M7N9DfgraobSwjet';
    const SERVER_LOGS = 'https://discord.com/api/webhooks/991631784582324224/MdApCn63OL7L6f6dSHs65bW7nEBKuCk5XhbaplAclJmEID1GuXE0M7N9DfgraobSwjet';
    const ERROR_LOGS = 'https://discord.com/api/webhooks/991631784582324224/MdApCn63OL7L6f6dSHs65bW7nEBKuCk5XhbaplAclJmEID1GuXE0M7N9DfgraobSwjet';
    private static bool $restarting = false;
    private static int $counter;

    public static function getAllWorlds(): array
    {
        $worlds = [];
        foreach (array_diff(scandir(Server::getInstance()->getDataPath() . "worlds"), [".."]) as $world) {
            $worlds[] = $world;
        }
        return $worlds;
    }

    public static function secondsToTicks(int $secs): int
    {
        return $secs * 20;
    }

    public static function minutesToTicks(int $minutes): int
    {
        return $minutes * 1200;
    }

    public static function hoursToTicks(int $hours): int
    {
        return $hours * 72000;
    }

    public static function setRestart(bool $restart = true): void
    {
        self::$restarting = $restart;
        if ($restart) {
            Erstric::getInstance()->getScheduler()->scheduleRepeatingTask(new ClosureTask(function (): void {
                if (self::$counter > 0) {
                    $msg = TextFormat::RED . "Restarting in " . self::$counter . ".";
                    foreach (Server::getInstance()->getOnlinePlayers() as $player) {
                        $player->sendMessage($msg);
                    }
                } elseif (self::$counter === -60) {
                    Server::getInstance()->shutdown();
                }
                self::$counter--;
            }), Utils::secondsToTicks(1));
        }
    }

    public static function isRestarting(): bool
    {
        return self::$restarting;
    }

    public static function sendStatus(): void
    {
        if (Server::getInstance()->isRunning()) $online = true;
        else $online = false;
        $message = new Message();
        $embed = new Embed();
        $message->setUsername('Skyetri');
        $embed->setTitle("SERVER STATUS");
        $embed->setDescription("The server is now " . ($online ? "online" : "offline"));
        $embed->setColor(($online ? 0x00FF00 : 0xFF0000));
        $embed->setTimestamp(new DateTime("NOW"));
        if (!$online && !Utils::isRestarting()) $message->setContent("The server has crashed, <@935933522416398356>.");
        $message->addEmbed($embed);
        $webhook = new Webhook(Utils::SERVER_LOGS);
        $webhook->send($message);
    }

    static function removeWorld(string $name): int
    {
        if (Erstric::getInstance()->getServer()->getWorldManager()->isWorldLoaded($name)) {
            $world = Server::getInstance()->getWorldManager()->getWorldByName($name);
            if (count($world->getPlayers()) > 0) {
                foreach ($world->getPlayers() as $player) {
                    $player->teleport(Erstric::getInstance()->getServer()->getWorldManager()->getDefaultWorld()->getSafeSpawn());
                }
            }

            Erstric::getInstance()->getServer()->getWorldManager()->unloadWorld($world);
        }

        $removedFiles = 1;

        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($worldPath = Erstric::getInstance()->getServer()->getDataPath() . "/worlds/$name", FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
        /** @var SplFileInfo $fileInfo */
        foreach ($files as $fileInfo) {
            if ($filePath = $fileInfo->getRealPath()) {
                if ($fileInfo->isFile()) {
                    unlink($filePath);
                } else {
                    rmdir($filePath);
                }

                $removedFiles++;
            }
        }

        rmdir($worldPath);
        return $removedFiles;
    }

    static function getWorldByNameNonNull(string $name): World
    {
        $world = Erstric::getInstance()->getServer()->getWorldManager()->getWorldByName($name);
        if ($world === null) {
            throw new AssumptionFailedError("Required world $name is null");
        }

        return $world;
    }

    static function renameWorld(string $oldName, string $newName): void
    {
        Utils::lazyUnloadWorld($oldName);

        $from = Erstric::getInstance()->getServer()->getDataPath() . "/worlds/" . $oldName;
        $to = Erstric::getInstance()->getServer()->getDataPath() . "/worlds/" . $newName;

        rename($from, $to);

        Utils::lazyLoadWorld($newName);
        $newWorld = Erstric::getInstance()->getServer()->getWorldManager()->getWorldByName($newName);
        if (!$newWorld instanceof World) {
            return;
        }

        $worldData = $newWorld->getProvider()->getWorldData();
        if (!$worldData instanceof BaseNbtWorldData) {
            return;
        }

        $worldData->getCompoundTag()->setString("LevelName", $newName);

        Erstric::getInstance()->getServer()->getWorldManager()->unloadWorld($newWorld); // reloading the world
        Utils::lazyLoadWorld($newName);
    }

    static function lazyUnloadWorld(string $name, bool $force = false): bool
    {
        if (($world = Erstric::getInstance()->getServer()->getWorldManager()->getWorldByName($name)) !== null) {
            return Erstric::getInstance()->getServer()->getWorldManager()->unloadWorld($world, $force);
        }
        return false;
    }

    static function lazyloadWorld(string $name, bool $force = false): bool
    {
        if (($world = Erstric::getInstance()->getServer()->getWorldManager()->getWorldByName($name)) !== null) {
            return Erstric::getInstance()->getServer()->getWorldManager()->unloadWorld($world, $force);
        }
        return false;
    }


    static function color(string $message): string
    {
        $message = str_replace("{BLACK}", TextFormat::BLACK, $message);
        $message = str_replace("{DARK_BLUE}", TextFormat::DARK_BLUE, $message);
        $message = str_replace("{DARK_GREEN}", TextFormat::DARK_GREEN, $message);
        $message = str_replace("{DARK_AQUA}", TextFormat::DARK_AQUA, $message);
        $message = str_replace("{DARK_RED}", TextFormat::DARK_RED, $message);
        $message = str_replace("{DARK_PURPLE}", TextFormat::DARK_PURPLE, $message);
        $message = str_replace("{GOLD}", TextFormat::GOLD, $message);
        $message = str_replace("{GRAY}", TextFormat::GRAY, $message);
        $message = str_replace("{DARK_GRAY}", TextFormat::DARK_GRAY, $message);
        $message = str_replace("{BLUE}", TextFormat::BLUE, $message);
        $message = str_replace("{GREEN}", TextFormat::GREEN, $message);
        $message = str_replace("{AQUA}", TextFormat::AQUA, $message);
        $message = str_replace("{RED}", TextFormat::RED, $message);
        $message = str_replace("{LIGHT_PURPLE}", TextFormat::LIGHT_PURPLE, $message);
        $message = str_replace("{YELLOW}", TextFormat::YELLOW, $message);
        $message = str_replace("{WHITE}", TextFormat::WHITE, $message);
        $message = str_replace("{OBFUSCATED}", TextFormat::OBFUSCATED, $message);
        $message = str_replace("{BOLD}", TextFormat::BOLD, $message);
        $message = str_replace("{STRIKETHROUGH}", TextFormat::STRIKETHROUGH, $message);
        $message = str_replace("{UNDERLINE}", TextFormat::UNDERLINE, $message);
        $message = str_replace("{ITALIC}", TextFormat::ITALIC, $message);
        $message = str_replace("{RESET}", TextFormat::RESET, $message);
        return $message;
    }

    static function changelogMessage(Player $player)
    {
        $player->sendMessage(TextFormat::colorize(str_repeat("&r&7-", 32)));
        $player->sendMessage(TextFormat::colorize("          &r&b&lEU Practice"));
        $player->sendMessage(TextFormat::colorize("&r&bSeason &r&f1 &r&7(Started 1 January 2022)"));
        $player->sendMessage(TextFormat::colorize("&r&bTo queue a match, &r&fright click the diamond sword."));
        $player->sendMessage(TextFormat::colorize("&r&bTo duel a player, do &r&f/duel [their name]"));
        $player->sendMessage(TextFormat::colorize("\n"));
        $player->sendMessage(TextFormat::colorize("          &r&b&lWHAT's NEW:"));
        $player->sendMessage(TextFormat::colorize("&r&b3 NEW Modes &r&f(Freeshop, Dragon, PearlFight)"));
        $player->sendMessage(TextFormat::colorize("&r&bNEW DUELS &r&f(Unranked/Ranked Duels)"));
        $player->sendMessage(TextFormat::colorize("&r&bFixed every known bug."));
        $player->sendMessage(TextFormat::colorize(str_repeat("&r&7-", 32)));

    }

    static function getPlayerPlatform(Player $player): string
    {
        $extraData = $player->getPlayerInfo()->getExtraData();

        if ($extraData["DeviceOS"] === DeviceOS::ANDROID && $extraData["DeviceModel"] === "") {
            return "Linux";
        }

        return match ($extraData["DeviceOS"]) {
            DeviceOS::ANDROID => "Android",
            DeviceOS::IOS => "iOS",
            DeviceOS::OSX => "macOS",
            DeviceOS::AMAZON => "FireOS",
            DeviceOS::GEAR_VR => "Gear VR",
            DeviceOS::HOLOLENS => "Hololens",
            DeviceOS::WINDOWS_10 => "Windows",
            DeviceOS::WIN32 => "Windows 7 (Edu)",
            DeviceOS::DEDICATED => "Dedicated",
            DeviceOS::TVOS => "TV OS",
            DeviceOS::PLAYSTATION => "PlayStation",
            DeviceOS::NINTENDO => "Nintendo Switch",
            DeviceOS::XBOX => "Xbox",
            DeviceOS::WINDOWS_PHONE => "Windows Phone",
            default => "Unknown"
        };
    }


    static function teleportToLobby(Player $player)
    {
        $player->teleport(Server::getInstance()->getWorldManager()->getWorldByName('lobby')->getSafeSpawn());
    }

    static function copy(string $from, string $to): void
    {
        if (is_dir($from)) {
            mkdir($to);
            $files = scandir($from);
            foreach ($files as $file) {
                if ($file != "." && $file != "..") {
                    self::copy("$from/$file", "$to/$file");
                }
            }
        } else if (file_exists($from)) {
            copy($from, $to);
        }
    }

}
