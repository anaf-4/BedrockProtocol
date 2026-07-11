<?php
/*
 * This file is part of BedrockProtocol.
 * Copyright (C) 2014-2022 PocketMine Team <https://github.com/pmmp/BedrockProtocol>
 *
 * BedrockProtocol is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */
declare(strict_types=1);
namespace pocketmine\network\mcpe\protocol;

use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\OverrideUpdateType;
use function is_finite;
use function is_nan;

/**
 * r/26_u4 (protocol 2169)부터:
 * - Target ID가 ActorRuntimeID가 아니라 ActorUniqueID로 변경됨
 *   (인코딩도 unsigned VarInt에서 signed VarInt로 실제로 바뀜 - 단순 명칭 변경이 아님)
 * - FloatOverride의 Value는 유한한(non-NaN, non-infinite) 값이어야 한다는 제약이 추가됨
 *
 * 참고: Mojang bedrock-protocol-docs, changelog_2168_07_07_26.md (r/26_u4)
 */
class PlayerUpdateEntityOverridesPacket extends DataPacket implements ClientboundPacket{
	public const NETWORK_ID = ProtocolInfo::PLAYER_UPDATE_ENTITY_OVERRIDES_PACKET;

	private int $targetActorUniqueId;
	private int $propertyIndex;
	private OverrideUpdateType $updateType;
	private ?int $intOverrideValue;
	private ?float $floatOverrideValue;

	/**
	 * @generate-create-func
	 */
	private static function create(int $targetActorUniqueId, int $propertyIndex, OverrideUpdateType $updateType, ?int $intOverrideValue, ?float $floatOverrideValue) : self{
		if($floatOverrideValue !== null){
			self::validateFiniteFloat($floatOverrideValue);
		}
		$result = new self;
		$result->targetActorUniqueId = $targetActorUniqueId;
		$result->propertyIndex = $propertyIndex;
		$result->updateType = $updateType;
		$result->intOverrideValue = $intOverrideValue;
		$result->floatOverrideValue = $floatOverrideValue;
		return $result;
	}

	private static function validateFiniteFloat(float $value) : void{
		if(is_nan($value) || !is_finite($value)){
			throw new PacketDecodeException("FloatOverride value must be finite (non-NaN, non-infinite)");
		}
	}

	public static function createIntOverride(int $targetActorUniqueId, int $propertyIndex, int $value) : self{
		return self::create($targetActorUniqueId, $propertyIndex, OverrideUpdateType::SET_INT_OVERRIDE, $value, null);
	}

	public static function createFloatOverride(int $targetActorUniqueId, int $propertyIndex, float $value) : self{
		return self::create($targetActorUniqueId, $propertyIndex, OverrideUpdateType::SET_FLOAT_OVERRIDE, null, $value);
	}

	public static function createClearOverrides(int $targetActorUniqueId, int $propertyIndex) : self{
		return self::create($targetActorUniqueId, $propertyIndex, OverrideUpdateType::CLEAR_OVERRIDES, null, null);
	}

	public static function createRemoveOverride(int $targetActorUniqueId, int $propertyIndex) : self{
		return self::create($targetActorUniqueId, $propertyIndex, OverrideUpdateType::REMOVE_OVERRIDE, null, null);
	}

	public function getTargetActorUniqueId() : int{ return $this->targetActorUniqueId; }

	public function getPropertyIndex() : int{ return $this->propertyIndex; }

	public function getUpdateType() : OverrideUpdateType{ return $this->updateType; }

	public function getIntOverrideValue() : ?int{ return $this->intOverrideValue; }

	public function getFloatOverrideValue() : ?float{ return $this->floatOverrideValue; }

	protected function decodePayload(ByteBufferReader $in) : void{
		$this->targetActorUniqueId = CommonTypes::getActorUniqueId($in);
		$this->propertyIndex = VarInt::readUnsignedInt($in);
		$this->updateType = OverrideUpdateType::fromPacket(Byte::readUnsigned($in));
		if($this->updateType === OverrideUpdateType::SET_INT_OVERRIDE){
			$this->intOverrideValue = LE::readSignedInt($in);
		}elseif($this->updateType === OverrideUpdateType::SET_FLOAT_OVERRIDE){
			$this->floatOverrideValue = LE::readFloat($in);
			self::validateFiniteFloat($this->floatOverrideValue);
		}
	}

	protected function encodePayload(ByteBufferWriter $out) : void{
		CommonTypes::putActorUniqueId($out, $this->targetActorUniqueId);
		VarInt::writeUnsignedInt($out, $this->propertyIndex);
		Byte::writeUnsigned($out, $this->updateType->value);
		if($this->updateType === OverrideUpdateType::SET_INT_OVERRIDE){
			if($this->intOverrideValue === null){ // this should never be the case
				throw new \LogicException("PlayerUpdateEntityOverridesPacket with type SET_INT_OVERRIDE requires intOverrideValue to be provided");
			}
			LE::writeSignedInt($out, $this->intOverrideValue);
		}elseif($this->updateType === OverrideUpdateType::SET_FLOAT_OVERRIDE){
			if($this->floatOverrideValue === null){ // this should never be the case
				throw new \LogicException("PlayerUpdateEntityOverridesPacket with type SET_FLOAT_OVERRIDE requires floatOverrideValue to be provided");
			}
			LE::writeFloat($out, $this->floatOverrideValue);
		}
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handlePlayerUpdateEntityOverrides($this);
	}
}