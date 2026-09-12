<?php

declare(strict_types=1);

namespace kostamax27\propertysync;

use InvalidArgumentException;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\network\mcpe\protocol\types\CacheableNbt;
use function array_keys;
use function array_map;
use function array_values;
use function count;
use function implode;
use function preg_match;

/**
 * Properties of a single actor identifier. Registration order decides the
 * indexes values are sent under, so it must not change once a client has been
 * given the definition.
 */
final class ActorPropertyList{
	public const MAX_PROPERTIES = 32;

	private const NAME_PATTERN = "/^[a-z0-9_.:-]*:[a-z0-9_.:-]*$/";

	/** @var array<string, ActorProperty> */
	private array $properties = [];

	/** @var array<string, int> */
	private array $indexes = [];

	/** @phpstan-var CacheableNbt<CompoundTag>|null */
	private ?CacheableNbt $nbt = null;

	public function __construct(
		readonly public string $actor_identifier
	){}

	/**
	 * @template TProperty of ActorProperty
	 * @param TProperty $property
	 * @return TProperty
	 */
	public function register(ActorProperty $property) : ActorProperty{
		$name = $property->getName();
		isset($this->properties[$name]) && throw new InvalidArgumentException("Property '{$name}' is already registered for {$this->actor_identifier}");
		preg_match(self::NAME_PATTERN, $name) === 1 || throw new InvalidArgumentException("Property '{$name}' expects a namespaced name matching " . self::NAME_PATTERN);
		count($this->properties) < self::MAX_PROPERTIES || throw new InvalidArgumentException("Cannot register more than " . self::MAX_PROPERTIES . " properties for {$this->actor_identifier}");
		$this->properties[$name] = $property;
		$this->indexes[$name] = count($this->indexes);
		$this->nbt = null;
		return $property;
	}

	public function getIndex(ActorProperty $property) : int{
		$name = $property->getName();
		return $this->indexes[$name] ?? throw new InvalidArgumentException("Property '{$name}' is not registered for {$this->actor_identifier}, expected one of " . implode(", ", array_keys($this->properties)));
	}

	/** @return array<string, ActorProperty> */
	public function getAll() : array{
		return $this->properties;
	}

	/** @phpstan-return CacheableNbt<CompoundTag> */
	public function getNbt() : CacheableNbt{
		return $this->nbt ??= new CacheableNbt(CompoundTag::create()
			->setTag("properties", new ListTag(array_map(static fn(ActorProperty $property) : CompoundTag => $property->toCompoundTag(), array_values($this->properties)), NBT::TAG_Compound))
			->setString("type", $this->actor_identifier));
	}
}
