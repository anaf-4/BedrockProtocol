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
 * @see SetScoreboardIdentityPacket
 *
 * r/26_u4 (protocol 2169)부터 Player Unique Id가 패킷 레벨 타입이 아니라
 * 엔트리별 presence byte(optional<int64_t>)로 결정됨.
 *
 * 참고: Mojang bedrock-protocol-docs, changelog_2168_07_07_26.md (r/26_u4)
 */
final class ScoreboardIdentityPacketEntry{

	public function __construct(
		private int $scoreboardId,
		private ?int $playerUniqueId,
	){}

	public function getScoreboardId() : int{ return $this->scoreboardId; }

	public function getPlayerUniqueId() : ?int{ return $this->playerUniqueId; }

	public static function read(ByteBufferReader $in) : self{
		$scoreboardId = VarInt::readSignedLong($in);
		$hasPlayerUniqueId = CommonTypes::getBool($in);
		$playerUniqueId = $hasPlayerUniqueId ? CommonTypes::getActorUniqueId($in) : null;
		return new self($scoreboardId, $playerUniqueId);
	}

	public function write(ByteBufferWriter $out) : void{
		VarInt::writeSignedLong($out, $this->scoreboardId);
		CommonTypes::putBool($out, $this->playerUniqueId !== null);
		if($this->playerUniqueId !== null){
			CommonTypes::putActorUniqueId($out, $this->playerUniqueId);
		}
	}
}