<?php

declare(strict_types=1);

namespace Krishvy\commands;

use Krishvy\libs\CortexPE\DiscordWebhookAPI\Embed;
use Krishvy\libs\CortexPE\DiscordWebhookAPI\Message;
use Krishvy\libs\CortexPE\DiscordWebhookAPI\Webhook;
use Krishvy\session\SessionFactory;
use Krishvy\utils\Utils;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use pocketmine\world\sound\XpCollectSound;

class ReplyCommand extends Command
{

    public function __construct()
    {
        parent::__construct("reply", "Reply to a message", "Usage: /reply <message>", ["r"]);
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void
    {
        if ($this->testPermission($sender) && $sender instanceof Player && ($session = SessionFactory::getSession($sender)) !== null) {
            if (count($args) > 0) {
                if (($replied = $session->getLastReplied()) !== "") {
                    if (($psession = SessionFactory::getSession($player = Server::getInstance()->getPlayerExact($replied))) !== null) {
                        $sentences = TextFormat::clean(trim(implode(" ", $args)));
                        if ($sender->isOnline()) {
                            $sender->sendMessage(TextFormat::GRAY . "To: " . TextFormat::WHITE . $player->getDisplayName() . ' ' . TextFormat::WHITE . $sentences);
                        }
                        if ($player->isOnline()) {
                            $player->sendMessage(TextFormat::GRAY . "From: " . TextFormat::WHITE . $sender->getDisplayName() . ' ' . TextFormat::WHITE . $sentences);
                            $player->broadcastSound(new XpCollectSound(), [$player]);
                        }
                        $session->setLastReplied($player->getName());
                        $psession->setLastReplied($sender->getName());
                        $webhook = new Webhook(Utils::CHAT_LOGS);
                        $message = new Message();
                        $embed = new Embed();
                        $embed->setTitle("Skyetri");
                        $embed->setDescription("Whisper: {$sender->getName()} » {$player->getName()} : $sentences");
                        $message->addEmbed($embed);
                        $webhook->send($message);
                        return;
                    }
                    $sender->sendMessage(TextFormat::RED . "That player is not online.");
                    return;
                }
                return;
            }
            $sender->sendMessage(TextFormat::RED . $this->getUsage());
        }
    }

}