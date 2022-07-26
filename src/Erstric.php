<?php

declare(strict_types=1);

namespace Krishvy;

use Krishvy\commands\FreezeCommand;
use Krishvy\commands\ReplyCommand;
use Krishvy\commands\RestartCommand;
use Krishvy\commands\SpawnCommand;
use Krishvy\commands\TellCommand;
use Krishvy\commands\TestCommand;
use Krishvy\session\SessionFactory;
use Krishvy\utils\Utils;
use pocketmine\command\Command;
use pocketmine\permission\Permission;
use pocketmine\permission\PermissionManager;
use pocketmine\permission\PermissionParser;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;
use pocketmine\Server;
use pocketmine\utils\SingletonTrait;

final class Erstric extends PluginBase
{
    use SingletonTrait;
    protected function onLoad(): void
    {
        self::setInstance($this);
    }

    protected function onEnable(): void
    {
        foreach (Utils::getAllWorlds() as $worlds) {
            $this->getServer()->getWorldManager()->loadWorld($worlds);
        }
        foreach(Server::getInstance()->getWorldManager()->getWorlds() as $world) {
            $world->setTime(0);
            $world->stopTime();
        }
        $this->getScheduler()->scheduleDelayedTask(new ClosureTask(function (): void {
            Utils::setRestart();
        }), Utils::hoursToTicks(12));
        $this->getServer()->getPluginManager()->registerEvents(new Events(), $this);
        Utils::sendStatus();
        $this->initPermissions();
        $this->initCommands();
        $this->getScheduler()->scheduleRepeatingTask(new ClosureTask(function (): void {
            foreach($this->getServer()->getOnlinePlayers() as $players){
                SessionFactory::getSession($players)->updateScoreboard();
            }
        }), 10);
    }

    protected function onDisable(): void
    {
        if (!$this->getServer()->isRunning()) {
            Utils::sendStatus();
        }
        SessionFactory::closeAllSessions();
    }

    public function initCommands(): void
    {
        $commands = [
            new SpawnCommand(),
            new TellCommand(),
            new ReplyCommand(),
            new FreezeCommand(),
            new TestCommand(),
            new RestartCommand(),
        ];
        $this->unregisterCommand($this->getServer()->getCommandMap()->getCommand("tell"));
        foreach ($commands as $command) {
            $this->registerCommand($command);
        }
    }

    public function initPermissions(): void
    {
        $permissions = [
            "skyetri.freeze" => PermissionParser::DEFAULT_OP,
            "skyetri.restart" => PermissionParser::DEFAULT_OP
        ];
        foreach ($permissions as $permission => $default) {
            PermissionManager::getInstance()->addPermission(new Permission($permission, $default));
        }

    }

    public function registerCommand(Command $command)
    {
        $this->getServer()->getCommandMap()->register($command->getName(), $command);
    }

    public function unregisterCommand(Command $command)
    {
        $this->getServer()->getCommandMap()->unregister($command);
    }
}
