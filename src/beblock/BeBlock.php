<?php
namespace beblock;

use pocketmine\block\CraftingTable;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\entity\Attribute;
use pocketmine\entity\Entity;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\network\mcpe\protocol\BlockEntityDataPacket;
use pocketmine\network\mcpe\protocol\BlockEventPacket;
use pocketmine\network\mcpe\protocol\UpdateBlockPacket;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;

class BeBlock extends PluginBase implements \pocketmine\event\Listener {
	public $players = [];
	public function onEnable() : void{
		$this->getServer()->getPluginManager()->registerEvents($this,$this);
	}
	public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
		if (!$sender instanceof Player) return true;
		if (isset($this->players[$sender->getName()])) {
			$sender->getDataPropertyManager()->setFloat(Entity::DATA_SCALE,1);
			unset($this->players[$sender->getName()]);
			$level = $sender->getLevel();
			$level->sendBlocks($level->getPlayers(), [$level->getBlock($sender->floor())],UpdateBlockPacket::FLAG_NEIGHBORS);
			return true;
		}
		$sender->getDataPropertyManager()->setFloat(Entity::DATA_SCALE,0);
		$this->players[$sender->getName()] = $sender;
		return true;
	}
	public function onPlayerMove(PlayerMoveEvent $event) {
		if (!isset($this->players[$event->getPlayer()->getName()])) {
			return;
		}
		if ([$event->getFrom()->getFloorX(),$event->getFrom()->getFloorY(),$event->getFrom()->getFloorZ()] == [$event->getTo()->getFloorX(),$event->getTo()->getFloorY(),$event->getTo()->getFloorZ()]) { //compare as string to increase speed
			return;
		}
		$block = new CraftingTable();
		$level = $event->getTo()->getLevel();
		$block->setLevel($level);
		$block->position($event->getTo());
		$targets = $level->getPlayers();
		unset($targets[array_search($event->getPlayer(),$targets)]);
		$level->sendBlocks($targets,[$block, $level->getBlock($event->getFrom())],UpdateBlockPacket::FLAG_ALL_PRIORITY);
	}
}