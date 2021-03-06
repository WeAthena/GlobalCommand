<?php

declare(strict_types=1);

namespace skh6075\globalcommand\command;

use pocketmine\command\Command;
use pocketmine\command\CommandMap;
use skh6075\globalcommand\Loader;

abstract class GlobalCommand extends Command{

	public function __construct(
		string $name,
		string $description,
		private array $params = []
	){
		parent::__construct($name, $description);
	}

	public function register(CommandMap $commandMap) : bool{
		if(!parent::register($commandMap)){
			return false;
		}
		Loader::getInstance()->register($this);
		return true;
	}

	public function unregister(CommandMap $commandMap) : bool{
		if(!parent::unregister($commandMap)){
			return false;
		}
		Loader::getInstance()->unregister($this);
		return true;
	}

	public function getParams(): array{
		return $this->params;
	}
}