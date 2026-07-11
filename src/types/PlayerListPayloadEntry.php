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

use pmmp\encoding\ByteBufferWriter;
use Ramsey\Uuid\UuidInterface;

/**
 * @see \pocketmine\network\mcpe\protocol\PlayerListPacket
 */
abstract class PlayerListPayloadEntry{
	public function __construct(
		private UuidInterface $uuid,
	){}

	abstract public function getActionId() : int;

	public function getUuid() : UuidInterface{ return $this->uuid; }

	abstract public function write(ByteBufferWriter $out) : void;
}