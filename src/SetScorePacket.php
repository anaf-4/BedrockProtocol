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
use pocketmine\network\mcpe\protocol\types\SetScoreEntryChangeEntity;
use pocketmine\network\mcpe\protocol\types\SetScoreEntryChangeFakePlayer;
use pocketmine\network\mcpe\protocol\types\SetScoreEntryChangePlayer;
use pocketmine\network\mcpe\protocol\types\SetScoreEntryRemove;
use pocketmine\network\mcpe\protocol\types\SetScorePayloadEntry;
use function count;

/**
 * r/26_u4 (protocol 2169)부터 완전히 재설계됨: 기존에는 패킷 레벨의 단일 $type
 * (TYPE_CHANGE/TYPE_REMOVE)이 모든 엔트리에 일괄 적용됐으나, 이제는 엔트리마다
 * 각자의 Action(Remove/ChangePlayer/ChangeEntity/ChangeFakePlayer)을 갖는
 * 태그드 유니온 구조로 바뀜.
 *
 * 참고: Mojang bedrock-protocol-docs, changelog_2168_07_07_26.md (r/26_u4)
 */
class SetScorePacket extends DataPacket implements ClientboundPacket{
	public const NETWORK_ID = ProtocolInfo::SET_SCORE_PACKET;

	/** @var SetScorePayloadEntry[] */
	private array $entries = [];

	/**
	 * @generate-create-func
	 * @param SetScorePayloadEntry[] $entries
	 */
	public static function create(array $entries) : self{
		$result = new self;
		$result->entries = $entries;
		return $result;
	}

	/**
	 * @return SetScorePayloadEntry[]
	 */
	public function getEntries() : array{ return $this->entries; }

	protected function decodePayload(ByteBufferReader $in) : void{
		$this->entries = [];
		for($i = 0, $len = VarInt::readUnsignedInt($in); $i < $len; ++$i){
			$this->entries[] = match(Byte::readUnsigned($in)){
				SetScoreEntryRemove::ID => SetScoreEntryRemove::read($in),
				SetScoreEntryChangePlayer::ID => SetScoreEntryChangePlayer::read($in),
				SetScoreEntryChangeEntity::ID => SetScoreEntryChangeEntity::read($in),
				SetScoreEntryChangeFakePlayer::ID => SetScoreEntryChangeFakePlayer::read($in),
				default => throw new PacketDecodeException("Unknown SetScorePacket entry action"),
			};
		}
	}

	protected function encodePayload(ByteBufferWriter $out) : void{
		VarInt::writeUnsignedInt($out, count($this->entries));
		foreach($this->entries as $entry){
			Byte::writeUnsigned($out, $entry->getActionId());
			$entry->write($out);
		}
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handleSetScore($this);
	}
}