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
use pocketmine\network\mcpe\protocol\types\PlayerListEntryAdd;
use pocketmine\network\mcpe\protocol\types\PlayerListEntryRemove;
use pocketmine\network\mcpe\protocol\types\PlayerListPayloadEntry;
use function count;

/**
 * r/26_u4 (protocol 2169)부터 완전히 재설계됨: 기존에는 패킷 레벨의 단일 $type
 * (TYPE_ADD/TYPE_REMOVE)이 모든 엔트리에 일괄 적용되고, Add 엔트리들의 스킨 검증
 * 플래그는 배열 전체를 순회한 뒤 별도로 몰아서 인코딩했으나(트레일링 배열),
 * 이제는 엔트리마다 각자의 Action(Add/Remove)을 갖고, 완전히 self-contained
 * 구조(검증 플래그도 인라인)로 바뀜.
 *
 * 참고: Mojang bedrock-protocol-docs, changelog_2168_07_07_26.md (r/26_u4)
 * 최대 1000개 엔트리로 제한됨(maxItems).
 */
class PlayerListPacket extends DataPacket implements ClientboundPacket{
	public const NETWORK_ID = ProtocolInfo::PLAYER_LIST_PACKET;

	private const MAX_ENTRIES = 1000;

	/** @var PlayerListPayloadEntry[] */
	private array $entries = [];

	/**
	 * @generate-create-func
	 * @param PlayerListPayloadEntry[] $entries
	 */
	public static function create(array $entries) : self{
		if(count($entries) > self::MAX_ENTRIES){
			throw new \InvalidArgumentException("PlayerListPacket cannot contain more than " . self::MAX_ENTRIES . " entries");
		}
		$result = new self;
		$result->entries = $entries;
		return $result;
	}

	/**
	 * @param PlayerListEntryAdd[] $entries
	 */
	public static function add(array $entries) : self{
		return self::create($entries);
	}

	/**
	 * @param PlayerListEntryRemove[] $entries
	 */
	public static function remove(array $entries) : self{
		return self::create($entries);
	}

	/**
	 * @return PlayerListPayloadEntry[]
	 */
	public function getEntries() : array{ return $this->entries; }

	protected function decodePayload(ByteBufferReader $in) : void{
		$this->entries = [];
		$count = VarInt::readUnsignedInt($in);
		if($count > self::MAX_ENTRIES){
			throw new PacketDecodeException("PlayerListPacket entry count $count exceeds maximum of " . self::MAX_ENTRIES);
		}
		for($i = 0; $i < $count; ++$i){
			$this->entries[] = match(Byte::readUnsigned($in)){
				PlayerListEntryAdd::ID => PlayerListEntryAdd::read($in),
				PlayerListEntryRemove::ID => PlayerListEntryRemove::read($in),
				default => throw new PacketDecodeException("Unknown PlayerListPacket entry action"),
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
		return $handler->handlePlayerList($this);
	}
}