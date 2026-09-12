<?php

declare(strict_types=1);

namespace kostamax27\propertysync;

use InvalidArgumentException;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\StringTag;
use function array_flip;
use function array_map;
use function count;
use function implode;

final class EnumActorProperty implements ActorProperty{
	public const TYPE = 3;

	public const MAX_VALUES = 16;

	/** @var array<string, int> */
	private array $indexes;

	/**
	 * @param string $name
	 * @param list<string> $values
	 */
	public function __construct(
		readonly public string $name,
		readonly public array $values
	){
		count($values) > 0 || throw new InvalidArgumentException("Property '{$name}' expects at least one value, got none");
		count($values) <= self::MAX_VALUES || throw new InvalidArgumentException("Property '{$name}' expects at most " . self::MAX_VALUES . " values, got " . count($values));
		$this->indexes = array_flip($values);
		count($this->indexes) === count($values) || throw new InvalidArgumentException("Property '{$name}' expects distinct values, got " . implode(", ", $values));
	}

	public function getName() : string{
		return $this->name;
	}

	public function applyDefault(ActorPropertyValues $values) : void{
		$values->setEnum($this, $this->values[0]);
	}

	public function indexOf(string $value) : int{
		return $this->indexes[$value] ?? throw new InvalidArgumentException("Property '{$this->name}' expects one of " . implode(", ", $this->values) . ", got '{$value}'");
	}

	public function toCompoundTag() : CompoundTag{
		return CompoundTag::create()
			->setTag("enum", new ListTag(array_map(static fn(string $value) : StringTag => new StringTag($value), $this->values), NBT::TAG_String))
			->setString("name", $this->name)
			->setInt("type", self::TYPE);
	}
}
