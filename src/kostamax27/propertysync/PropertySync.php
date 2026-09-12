<?php

declare(strict_types=1);

namespace kostamax27\propertysync;

use BadMethodCallException;
use InvalidArgumentException;
use pocketmine\entity\Entity;
use pocketmine\plugin\Plugin;
use pocketmine\Server;
use PrefixedLogger;
use ReflectionClass;

final class PropertySync{

	private static ActorPropertyManager $manager;

	public static function isRegistered() : bool{
		return isset(self::$manager);
	}

	public static function register(Plugin $plugin) : void{
		isset(self::$manager) && throw new BadMethodCallException(__CLASS__ . " is already registered");
		self::$manager = new ActorPropertyManager($plugin);
	}

	/**
	 * Properties of an actor identifier. Register them on plugin enable, before
	 * anyone joins: registration order decides the indexes values are sent
	 * under.
	 */
	public static function list(string $actor_identifier) : ActorPropertyList{
		return (self::$manager ?? self::managerNotFound())->list($actor_identifier);
	}

	/**
	 * Property values of an actor. Call $entity->sendData(null) after changing
	 * them, the packet will carry the new values.
	 */
	public static function of(Entity $entity) : ActorPropertyValues{
		return (self::$manager ?? self::managerNotFound())->of($entity);
	}

	public static function forget(Entity $entity) : void{
		(self::$manager ?? self::managerNotFound())->forget($entity);
	}

	private static function managerNotFound() : ActorPropertyManager{
		$class = new ReflectionClass(self::class);
		$logger = new PrefixedLogger(Server::getInstance()->getLogger(), $class->getShortName());
		$logger->warning("Property \$manager has not been initialized.");
		$logger->warning("This means you likely forgot to register this library on plugin enable.");
		$logger->warning("You can address this error by updating your plugin's onEnable method as below:");
		$logger->warning("```");
		$logger->warning("use {$class->name};");
		$logger->warning("protected function onEnable() : void{");
		$logger->warning("    if(!{$class->getShortName()}::isRegistered()){");
		$logger->warning("        {$class->getShortName()}::register(\$this);");
		$logger->warning("    }");
		$logger->warning("}");
		$logger->warning("```");
		throw new InvalidArgumentException("Could not find actor property manager");
	}
}
