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
namespace pocketmine\network\mcpe\protocol\types;

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use Ramsey\Uuid\UuidInterface;

/**
 * @see \pocketmine\network\mcpe\protocol\TransferPacket
 *
 * r/26_u4 (protocol 2169)에서 TransferPacket에 새로 추가된 선택적 필드가 담는 구조.
 * 필드 순서: experienceId, experienceName, worldId, worldName, creatorId, targetId, scenarioId, serverId.
 *
 * 참고: Mojang bedrock-protocol-docs, changelog_2168_07_07_26.md (r/26_u4)
 */
final class GatheringsConfiguration{
	public function __construct(
		private UuidInterface $experienceId,
		private string $experienceName,
		private UuidInterface $worldId,
		private string $worldName,
		private string $creatorId,
		private UuidInterface $targetId,
		private string $scenarioId,
		private string $serverId,
	){}

	public function getExperienceId() : UuidInterface{ return $this->experienceId; }

	public function getExperienceName() : string{ return $this->experienceName; }

	public function getWorldId() : UuidInterface{ return $this->worldId; }

	public function getWorldName() : string{ return $this->worldName; }

	public function getCreatorId() : string{ return $this->creatorId; }

	public function getTargetId() : UuidInterface{ return $this->targetId; }

	public function getScenarioId() : string{ return $this->scenarioId; }

	public function getServerId() : string{ return $this->serverId; }

	public static function read(ByteBufferReader $in) : self{
		$experienceId = CommonTypes::getUUID($in);
		$experienceName = CommonTypes::getString($in);
		$worldId = CommonTypes::getUUID($in);
		$worldName = CommonTypes::getString($in);
		$creatorId = CommonTypes::getString($in);
		$targetId = CommonTypes::getUUID($in);
		$scenarioId = CommonTypes::getString($in);
		$serverId = CommonTypes::getString($in);
		return new self($experienceId, $experienceName, $worldId, $worldName, $creatorId, $targetId, $scenarioId, $serverId);
	}

	public function write(ByteBufferWriter $out) : void{
		CommonTypes::putUUID($out, $this->experienceId);
		CommonTypes::putString($out, $this->experienceName);
		CommonTypes::putUUID($out, $this->worldId);
		CommonTypes::putString($out, $this->worldName);
		CommonTypes::putString($out, $this->creatorId);
		CommonTypes::putUUID($out, $this->targetId);
		CommonTypes::putString($out, $this->scenarioId);
		CommonTypes::putString($out, $this->serverId);
	}
}