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
use pmmp\encoding\LE;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\GatheringsConfiguration;

/**
 * r/26_u4 (protocol 2169)부터 선택적 Gatherings Configuration 필드가 추가됨
 * (gatherings 컨텍스트를 포함해 플레이어를 전송할 수 있게 함).
 *
 * 참고: Mojang bedrock-protocol-docs, changelog_2168_07_07_26.md (r/26_u4)
 */
class TransferPacket extends DataPacket implements ClientboundPacket{
	public const NETWORK_ID = ProtocolInfo::TRANSFER_PACKET;

	public string $address;
	public int $port = 19132;
	public bool $reloadWorld;
	public ?GatheringsConfiguration $gatheringsConfiguration = null;

	/**
	 * @generate-create-func
	 */
	public static function create(string $address, int $port, bool $reloadWorld, ?GatheringsConfiguration $gatheringsConfiguration = null) : self{
		$result = new self;
		$result->address = $address;
		$result->port = $port;
		$result->reloadWorld = $reloadWorld;
		$result->gatheringsConfiguration = $gatheringsConfiguration;
		return $result;
	}

	protected function decodePayload(ByteBufferReader $in) : void{
		$this->address = CommonTypes::getString($in);
		$this->port = LE::readUnsignedShort($in);
		$this->reloadWorld = CommonTypes::getBool($in);
		$this->gatheringsConfiguration = CommonTypes::readOptional($in, fn() => GatheringsConfiguration::read($in));
	}

	protected function encodePayload(ByteBufferWriter $out) : void{
		CommonTypes::putString($out, $this->address);
		LE::writeUnsignedShort($out, $this->port);
		CommonTypes::putBool($out, $this->reloadWorld);
		CommonTypes::writeOptional($out, $this->gatheringsConfiguration, fn(ByteBufferWriter $out, GatheringsConfiguration $config) => $config->write($out));
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handleTransfer($this);
	}
}