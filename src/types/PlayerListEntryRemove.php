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

/**
 * @see PlayerListPayloadEntry
 * @see \pocketmine\network\mcpe\protocol\PlayerListPacket
 */
final class PlayerListEntryRemove extends PlayerListPayloadEntry{
	public const ID = 1; //TYPE_REMOVE, matches legacy packet-level convention

	public function getActionId() : int{
		return self::ID;
	}

	/**
	 * Action 바이트는 외부(PlayerListPacket::decodePayload)에서 이미 읽음.
	 */
	public static function read(ByteBufferReader $in) : self{
		return new self(CommonTypes::getUUID($in));
	}

	public function write(ByteBufferWriter $out) : void{
		CommonTypes::putUUID($out, $this->getUuid());
	}
}