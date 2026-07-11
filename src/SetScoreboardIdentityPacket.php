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
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\types\ScoreboardIdentityPacketEntry;
use function count;

/**
 * r/26_u4 (protocol 2169)부터 mPlayerId가 패킷 타입이 아니라 엔트리별
 * presence byte로 결정됨 (packet-level type 자체는 그대로 유지됨).
 *
 * 참고: Mojang bedrock-protocol-docs, changelog_2168_07_07_26.md (r/26_u4)
 */
class SetScoreboardIdentityPacket extends DataPacket implements ClientboundPacket{
	public const NETWORK_ID = ProtocolInfo::SET_SCOREBOARD_IDENTITY_PACKET;

	public const TYPE_REGISTER_IDENTITY = 0; // "Update"
	public const TYPE_CLEAR_IDENTITY = 1; // "Remove"

	public int $type;
	/** @var ScoreboardIdentityPacketEntry[] */
	public array $entries = [];

	/**
	 * @generate-create-func
	 * @param ScoreboardIdentityPacketEntry[] $entries
	 */
	public static function create(int $type, array $entries) : self{
		$result = new self;
		$result->type = $type;
		$result->entries = $entries;
		return $result;
	}

	protected function decodePayload(ByteBufferReader $in) : void{
		$this->type = Byte::readUnsigned($in);
		$this->entries = [];
		for($i = 0, $count = VarInt::readUnsignedInt($in); $i < $count; ++$i){
			$this->entries[] = ScoreboardIdentityPacketEntry::read($in);
		}
	}

	protected function encodePayload(ByteBufferWriter $out) : void{
		Byte::writeUnsigned($out, $this->type);
		VarInt::writeUnsignedInt($out, count($this->entries));
		foreach($this->entries as $entry){
			$entry->write($out);
		}
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handleSetScoreboardIdentity($this);
	}
}