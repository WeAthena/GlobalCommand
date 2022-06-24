<?php

declare(strict_types=1);

namespace skh6075\globalcommand;

use kim\present\libmultilingual\Language;
use kim\present\libmultilingual\Translator;
use pocketmine\command\Command;
use pocketmine\event\EventPriority;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\network\mcpe\protocol\AvailableCommandsPacket;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\SingletonTrait;
use skh6075\globalcommand\command\GlobalCommand;
use Webmozart\PathUtil\Path;

final class Loader extends PluginBase{
	use SingletonTrait;

	public static function getInstance() : Loader{
		return self::$instance;
	}

	/**
	 * @phpstan-var array<int, GlobalCommand>
	 * @var GlobalCommand[]
	 */
	private array $commands = [];

	private Translator $translator;

	public function getGlobalCommand(Command|int $command): ?GlobalCommand{
		if($command instanceof Command){
			$command = spl_object_id($command);
		}
		return $this->commands[$command] ?? null;
	}

	public function register(GlobalCommand $command): void{
		$this->commands[spl_object_id($command)] = $command;
	}

	public function unregister(GlobalCommand $command): void{
		$id = spl_object_id($command);
		if(!isset($this->commands[$id])){
			return;
		}
		unset($this->commands[$id]);
	}

	protected function onLoad() : void{
		self::$instance = $this;
	}

	protected function onEnable() : void{
		$this->saveResource("locale/kor.ini");
		$this->saveResource("locale/eng.ini");
		$this->translator = new Translator([
			Language::fromFile(Path::join($this->getDataFolder(), "locale/", "kor.ini"), "kor"),
			Language::fromFile(Path::join($this->getDataFolder(), "locale/", "eng.ini"), "eng")
		]);

		$this->getServer()->getPluginManager()->registerEvent(DataPacketSendEvent::class, function(DataPacketSendEvent $event): void{
			foreach($event->getPackets() as $packet){
				if(!$packet instanceof AvailableCommandsPacket){
					continue;
				}
				foreach($event->getTargets() as $target){
					$player = $target->getPlayer();
					if($player === null){
						continue;
					}
					foreach($packet->commandData as $name => $commandDatum){
						/** @var GlobalCommand $command */
						$command = $this->getServer()->getCommandMap()->getCommand($name);
						if($command === null || !$this->getGlobalCommand($command)){
							continue;
						}
						$commandDatum->description = $this->translator->translate($command->getDescription(), $command->getParams(), $player);
					}
				}
			}
		}, EventPriority::HIGHEST, $this);

		$this->getServer()->getPluginManager()->registerEvent(PlayerJoinEvent::class, fn(PlayerJoinEvent $event) => $event->getPlayer()->getNetworkSession()->syncAvailableCommands(), EventPriority::MONITOR, $this);
	}
}