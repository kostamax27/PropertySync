<?php

declare(strict_types=1);

namespace kostamax27\propertysync;

use InvalidArgumentException;
use pocketmine\network\mcpe\protocol\types\entity\PropertySyncData;

/**
 * Property values of a single actor. Each setter takes the property it belongs
 * to, which is what keeps the value types honest. They return whether anything
 * changed, so the caller can skip sending a packet.
 *
 * Indexes run across the whole list: the client already knows from the
 * definition which of them are integers and which are floats.
 */
final class ActorPropertyValues{

	/** @var array<int, int> */
	private array $int_values = [];

	/** @var array<int, float> */
	private array $float_values = [];

	public function __construct(
		readonly public ActorPropertyList $list
	){
		foreach($list->getAll() as $property){ // the client expects a value for every property
			$property->applyDefault($this);
		}
	}

	public function setBool(BoolActorProperty $property, bool $value) : bool{
		return $this->writeInt($this->list->getIndex($property), $value ? 1 : 0);
	}

	public function setInt(IntActorProperty $property, int $value) : bool{
		($value >= $property->min && $value <= $property->max) || throw new InvalidArgumentException("Property '{$property->name}' expects a value between {$property->min} and {$property->max}, got {$value}");
		return $this->writeInt($this->list->getIndex($property), $value);
	}

	public function setFloat(FloatActorProperty $property, float $value) : bool{
		($value >= $property->min && $value <= $property->max) || throw new InvalidArgumentException("Property '{$property->name}' expects a value between {$property->min} and {$property->max}, got {$value}");
		return $this->writeFloat($this->list->getIndex($property), $value);
	}

	public function setEnum(EnumActorProperty $property, string $value) : bool{
		return $this->writeInt($this->list->getIndex($property), $property->indexOf($value));
	}

	public function toSyncData() : PropertySyncData{
		return new PropertySyncData($this->int_values, $this->float_values);
	}

	private function writeInt(int $index, int $value) : bool{
		if(($this->int_values[$index] ?? null) === $value){
			return false;
		}
		$this->int_values[$index] = $value;
		return true;
	}

	private function writeFloat(int $index, float $value) : bool{
		if(($this->float_values[$index] ?? null) === $value){
			return false;
		}
		$this->float_values[$index] = $value;
		return true;
	}
}
