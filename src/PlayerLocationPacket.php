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

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\PlayerLocationType;

/**
 * r/26_u4 (protocol 2169)부터 필드 순서와 discriminator 인코딩이 변경됨:
 * - 기존: [discriminator: LE uint32] [actorUniqueId] [optional position]
 * - 신규: [actorUniqueId] [discriminator: VarInt signed int] [optional position]
 *   (discriminator가 고정폭 LE에서 VarInt 압축 인코딩으로 전환됨)
 *
 * 참고: Mojang bedrock-protocol-docs, changelog_2168_07_07_26.md (r/26_u4)
 */
class PlayerLocationPacket extends DataPacket implements ClientboundPacket{
	public const NETWORK_ID = ProtocolInfo::PLAYER_LOCATION_PACKET;

	private PlayerLocationType $type;
	private int $actorUniqueId;
	private ?Vector3 $position;

	/**
	 * @generate-create-func
	 */
	private static function create(PlayerLocationType $type, int $actorUniqueId, ?Vector3 $position) : self{
		$result = new self;
		$result->type = $type;
		$result->actorUniqueId = $actorUniqueId;
		$result->position = $position;
		return $result;
	}

	public static function createCoordinates(int $actorUniqueId, Vector3 $position) : self{
		return self::create(PlayerLocationType::PLAYER_LOCATION_COORDINATES, $actorUniqueId, $position);
	}

	public static function createHide(int $actorUniqueId) : self{
		return self::create(PlayerLocationType::PLAYER_LOCATION_HIDE, $actorUniqueId, null);
	}

	public function getType() : PlayerLocationType{ return $this->type; }

	public function getActorUniqueId() : int{ return $this->actorUniqueId; }

	public function getPosition() : ?Vector3{ return $this->position; }

	protected function decodePayload(ByteBufferReader $in) : void{
		$this->actorUniqueId = CommonTypes::getActorUniqueId($in);
		$this->type = PlayerLocationType::fromPacket(VarInt::readSignedInt($in));
		if($this->type === PlayerLocationType::PLAYER_LOCATION_COORDINATES){
			$this->position = CommonTypes::getVector3($in);
		}else{
			$this->position = null;
		}
	}

	protected function encodePayload(ByteBufferWriter $out) : void{
		CommonTypes::putActorUniqueId($out, $this->actorUniqueId);
		VarInt::writeSignedInt($out, $this->type->value);
		if($this->type === PlayerLocationType::PLAYER_LOCATION_COORDINATES){
			if($this->position === null){ // this should never be the case
				throw new \LogicException("PlayerLocationPacket with type PLAYER_LOCATION_COORDINATES require a position to be provided");
			}
			CommonTypes::putVector3($out, $this->position);
		}
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handlePlayerLocation($this);
	}
}