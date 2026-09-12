<?php

declare(strict_types=1);

namespace kostamax27\propertysync;

use pocketmine\nbt\tag\CompoundTag;

/**
 * An immutable property definition. Values are set through the typed setters on
 * {@link ActorPropertyValues}, so a property never has to describe its own
 * storage.
 */
interface ActorProperty{

	public function getName() : string;

	/**
	 * The definition as sent in SyncActorPropertyPacket.
	 */
	public function toCompoundTag() : CompoundTag;

	/**
	 * Writes this property's default value. The client expects every property
	 * to carry a value, so the set is filled in on creation.
	 */
	public function applyDefault(ActorPropertyValues $values) : void;
}
