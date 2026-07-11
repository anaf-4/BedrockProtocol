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
use pmmp\encoding\DataDecodeException;
use pmmp\encoding\LE;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

/**
 * r/26_u4 (protocol 2169)부터 FLAG_FORCE_COMPLETION 비트가 추가됨 ("Force Completion":
 * 수신자가 이 업데이트를 적용하기 전에 진행 중인 로컬 이동을 완료해야 하는지 여부).
 * 기존 $flags 필드가 이미 통째로(uint16 LE) 직렬화되므로, 이 비트를 추가하는 것만으로
 * 인코딩/디코딩 로직 변경 없이 자동 반영됨.
 *
 * 참고: Mojang bedrock-protocol-docs, changelog_2168_07_07_26.md (r/26_u4)
 */
class MoveActorDeltaPacket extends DataPacket implements ClientboundPacket{
	public const NETWORK_ID = ProtocolInfo::MOVE_ACTOR_DELTA_PACKET;

	public const FLAG_HAS_X = 0x01;
	public const FLAG_HAS_Y = 0x02;
	public const FLAG_HAS_Z = 0x04;
	public const FLAG_HAS_PITCH = 0x08;
	public const FLAG_HAS_YAW = 0x10;
	public const FLAG_HAS_HEAD_YAW = 0x20;
	public const FLAG_GROUND = 0x40;
	public const FLAG_TELEPORT = 0x80;
	public const FLAG_FORCE_MOVE_LOCAL_ENTITY = 0x100;
	public const FLAG_FORCE_COMPLETION = 0x200;

	public int $actorRuntimeId;
	public int $flags;
	public float $xPos = 0;
	public float $yPos = 0;
	public float $zPos = 0;
	public float $pitch = 0.0;
	public float $yaw = 0.0;
	public float $headYaw = 0.0;

	/** @throws DataDecodeException */
	private function maybeReadCoord(int $flag, ByteBufferReader $in) : float{
		if(($this->flags & $flag) !== 0){
			return LE::readFloat($in);
		}
		return 0;
	}

	/** @throws DataDecodeException */
	private function maybeReadRotation(int $flag, ByteBufferReader $in) : float{
		if(($this->flags & $flag) !== 0){
			return CommonTypes::getRotationByte($in);
		}
		return 0.0;
	}

	protected function decodePayload(ByteBufferReader $in) : void{
		$this->actorRuntimeId = CommonTypes::getActorRuntimeId($in);
		$this->flags = LE::readUnsignedShort($in);
		$this->xPos = $this->maybeReadCoord(self::FLAG_HAS_X, $in);
		$this->yPos = $this->maybeReadCoord(self::FLAG_HAS_Y, $in);
		$this->zPos = $this->maybeReadCoord(self::FLAG_HAS_Z, $in);
		$this->pitch = $this->maybeReadRotation(self::FLAG_HAS_PITCH, $in);
		$this->yaw = $this->maybeReadRotation(self::FLAG_HAS_YAW, $in);
		$this->headYaw = $this->maybeReadRotation(self::FLAG_HAS_HEAD_YAW, $in);
	}

	private function maybeWriteCoord(int $flag, float $val, ByteBufferWriter $out) : void{
		if(($this->flags & $flag) !== 0){
			LE::writeFloat($out, $val);
		}
	}

	private function maybeWriteRotation(int $flag, float $val, ByteBufferWriter $out) : void{
		if(($this->flags & $flag) !== 0){
			CommonTypes::putRotationByte($out, $val);
		}
	}

	protected function encodePayload(ByteBufferWriter $out) : void{
		CommonTypes::putActorRuntimeId($out, $this->actorRuntimeId);
		LE::writeUnsignedShort($out, $this->flags);
		$this->maybeWriteCoord(self::FLAG_HAS_X, $this->xPos, $out);
		$this->maybeWriteCoord(self::FLAG_HAS_Y, $this->yPos, $out);
		$this->maybeWriteCoord(self::FLAG_HAS_Z, $this->zPos, $out);
		$this->maybeWriteRotation(self::FLAG_HAS_PITCH, $this->pitch, $out);
		$this->maybeWriteRotation(self::FLAG_HAS_YAW, $this->yaw, $out);
		$this->maybeWriteRotation(self::FLAG_HAS_HEAD_YAW, $this->headYaw, $out);
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handleMoveActorDelta($this);
	}
}