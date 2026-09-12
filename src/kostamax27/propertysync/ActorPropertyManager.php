<?php

declare(strict_types=1);

namespace kostamax27\propertysync;

use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDespawnEvent;
use pocketmine\event\EventPriority;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\network\mcpe\protocol\AddPlayerPacket;
use pocketmine\network\mcpe\protocol\SetActorDataPacket;
use pocketmine\network\mcpe\protocol\StartGamePacket;
use pocketmine\network\mcpe\protocol\SyncActorPropertyPacket;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\plugin\Plugin;

/**
 * PocketMine sends an empty PropertySyncData in every packet of its own and
 * never sends the definitions at all, so both are written into outgoing packets
 * here.
 */
final class ActorPropertyManager{

	/** @var array<string, ActorPropertyList> */
	private array $lists = [];

	/** @var array<int, ActorPropertyValues> */
	private array $values = [];

	public function __construct(Plugin $plugin){
		$plugin_manager = $plugin->getServer()->getPluginManager();
		$plugin_manager->registerEvent(DataPacketSendEvent::class, $this->onDataPacketSend(...), EventPriority::NORMAL, $plugin);
		$plugin_manager->registerEvent(EntityDespawnEvent::class, $this->onEntityDespawn(...), EventPriority::MONITOR, $plugin);
	}

	public function list(string $actor_identifier) : ActorPropertyList{
		return $this->lists[$actor_identifier] ??= new ActorPropertyList($actor_identifier);
	}

	public function of(Entity $entity) : ActorPropertyValues{
		return $this->values[$entity->getId()] ??= new ActorPropertyValues($this->list($entity->getNetworkTypeId()));
	}

	/**
	 * Values are dropped when the actor despawns — this only releases them
	 * earlier.
	 */
	public function forget(Entity $entity) : void{
		unset($this->values[$entity->getId()]);
	}

	/**
	 * @priority MONITOR
	 */
	private function onEntityDespawn(EntityDespawnEvent $event) : void{
		$this->forget($event->getEntity());
	}

	private function onDataPacketSend(DataPacketSendEvent $event) : void{
		$packets = $event->getPackets();
		$definitions_sent = false;

		foreach($packets as $packet){
			if($packet instanceof StartGamePacket){
				$packet->playerActorProperties = $this->list(EntityIds::PLAYER)->getNbt();
				$definitions_sent = true;
			}elseif($packet instanceof AddPlayerPacket || $packet instanceof AddActorPacket || $packet instanceof SetActorDataPacket){
				$values = $this->values[$packet->actorRuntimeId] ?? null;
				if($values !== null){
					$packet->syncedProperties = $values->toSyncData();
				}
			}
		}

		if(!$definitions_sent){
			return;
		}

		foreach($this->lists as $list){ // the player list goes out twice on purpose, the client wants it that way
			$packets[] = SyncActorPropertyPacket::create($list->getNbt());
		}
		$event->setPackets($packets);
	}
}
