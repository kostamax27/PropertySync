# PropertySync
Entity properties for PocketMine-MP, readable from resource packs as `query.property`.

## Approach
Declare the properties of an actor identifier once, keep the objects the registration hands back,
and set values through them. Definitions and values are written into the packets PocketMine was
going to send anyway.

```php
$list = PropertySync::list(EntityIds::PLAYER);
$this->my_property = $list->register(new BoolActorProperty("custom:property"));

$values = PropertySync::of($player);
if($values->setBool($this->my_property, true)){
	$player->sendData(null);
}
```

In the pack:

```json
"variable.my_property = query.property('custom:property');"
```

### Registration
Registration order decides the indexes values are sent under, so properties are declared on plugin
enable, before anyone joins, and never reordered afterwards.

```php
protected function onEnable() : void{
	if(!PropertySync::isRegistered()){
		PropertySync::register($this);
	}

	$list = PropertySync::list(EntityIds::PLAYER);
	$this->my_property = $list->register(new BoolActorProperty("custom:property"));
}
```

Up to 32 properties per actor identifier, names must be namespaced, enums hold at most 16 values.

### When a value does not change
Setters return whether anything changed, which is what decides if a packet is worth sending.

```php
if($values->setBool($this->my_property, true)){
	$player->sendData(null);
}
```

## Examples

### 1. A model selector
```php
$this->slim_model = $list->register(new BoolActorProperty("skin:slim"));

$values->setBool($this->slim_model, str_contains($player->getSkin()->getGeometryName(), "slim"));
$player->sendData(null);
```

```json
"geometry": "query.property('skin:slim') ? Geometry.slim : Geometry.default"
```

### 2. Named states instead of magic numbers
```php
$this->state = $list->register(new EnumActorProperty("duel:state", ["idle", "queued", "fighting"]));

$values->setEnum($this->state, "fighting");
```

A wrong string throws at the call site listing the accepted values, rather than rendering the
wrong thing silently.

## Property types
| Class                | `type` in NBT | Setter                                | Default                   |
|----------------------|---------------|---------------------------------------|---------------------------|
| `IntActorProperty`   | 0             | `setInt(IntActorProperty, int)`       | `min`, or the one given   |
| `FloatActorProperty` | 1             | `setFloat(FloatActorProperty, float)` | `min`, or the one given   |
| `BoolActorProperty`  | 2             | `setBool(BoolActorProperty, bool)`    | `false`, or the one given |
| `EnumActorProperty`  | 3             | `setEnum(EnumActorProperty, string)`  | the first value           |

Bools and enums travel in the integer list of `PropertySyncData`, floats in the float list. Indexes
run across the whole property list: the client knows from the definition which of them are which.

Every property starts at its default, so an actor always has a complete set to send — the client
expects a value for each.

Values are written into `AddPlayerPacket`, `AddActorPacket` and `SetActorDataPacket` as they leave.

## Limitations
- Definitions go out with `StartGamePacket`, so properties registered after a player joined do not
  reach them until they reconnect.
- Values need a packet to travel: `$entity->sendData(null)` for an actor that is already spawned,
  or a respawn.
- Nothing is persisted. Set the values again after a restart.
