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
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

/**
 * @see SetScorePacket
 *
 * 참고: Objective Name은 스펙상 선택적 필드(required 목록에 없음)입니다.
 * 이 마이그레이션 전반에서 선택적 필드는 presence bool + 값으로 인코딩되는 관례를
 * 따랐다고 가정했습니다 - 실제 패킷 캡처로 아직 검증되지 않았습니다.
 */
final class SetScoreEntryRemove extends SetScorePayloadEntry{
	public const ID = ScorePacketEntryActionType::REMOVE;

	public function __construct(
		int $scoreboardId,
		private ?string $objectiveName,
	){
		parent::__construct($scoreboardId);
	}

	public function getActionId() : int{
		return self::ID;
	}

	public function getObjectiveName() : ?string{ return $this->objectiveName; }

	/**
	 * Action 바이트는 외부(SetScorePacket::decodePayload)에서 이미 읽고 소비함.
	 * 여기서는 Action 다음에 오는 필드(Scoreboard Id, 선택적 Objective Name)만 읽음.
	 */
	public static function read(ByteBufferReader $in) : self{
		$scoreboardId = VarInt::readSignedLong($in);
		$hasObjectiveName = CommonTypes::getBool($in);
		$objectiveName = $hasObjectiveName ? CommonTypes::getString($in) : null;
		return new self($scoreboardId, $objectiveName);
	}

	public function write(ByteBufferWriter $out) : void{
		VarInt::writeSignedLong($out, $this->getScoreboardId());
		CommonTypes::putBool($out, $this->objectiveName !== null);
		if($this->objectiveName !== null){
			CommonTypes::putString($out, $this->objectiveName);
		}
	}
}
