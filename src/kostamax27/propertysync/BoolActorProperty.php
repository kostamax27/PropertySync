<?php

declare(strict_types=1);

namespace kostamax27\propertysync;

use pocketmine\nbt\tag\CompoundTag;

final class BoolActorProperty implements ActorProperty{
	public const TYPE = 2;

	public function __construct(
		readonly public string $name,
		readonly public bool $default_value = false
	){}

	public function getName() : string{
		return $this->name;
	}

	public function applyDefault(ActorPropertyValues $values) : void{
		$values->setBool($this, $this->default_value);
	}

	public function toCompoundTag() : CompoundTag{
		return CompoundTag::create()
			->setString("name", $this->name)
			->setInt("type", self::TYPE);
	}
}
