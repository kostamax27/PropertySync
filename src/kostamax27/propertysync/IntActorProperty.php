<?php

declare(strict_types=1);

namespace kostamax27\propertysync;

use InvalidArgumentException;
use pocketmine\nbt\tag\CompoundTag;

final class IntActorProperty implements ActorProperty{
	public const TYPE = 0;

	readonly public int $default_value;

	public function __construct(
		readonly public string $name,
		readonly public int $min,
		readonly public int $max,
		?int $default_value = null
	){
		$min <= $max || throw new InvalidArgumentException("Property '{$name}' expects min <= max, got {$min} > {$max}");
		$default_value ??= $min;
		($default_value >= $min && $default_value <= $max) || throw new InvalidArgumentException("Property '{$name}' expects a default between {$min} and {$max}, got {$default_value}");
		$this->default_value = $default_value;
	}

	public function getName() : string{
		return $this->name;
	}

	public function applyDefault(ActorPropertyValues $values) : void{
		$values->setInt($this, $this->default_value);
	}

	public function toCompoundTag() : CompoundTag{
		return CompoundTag::create()
			->setInt("max", $this->max)
			->setInt("min", $this->min)
			->setString("name", $this->name)
			->setInt("type", self::TYPE);
	}
}
