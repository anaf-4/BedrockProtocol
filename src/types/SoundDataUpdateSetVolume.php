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
use pmmp\encoding\LE;

/**
 * @see ClientboundUpdateSoundDataPacket
 */
final class SoundDataUpdateSetVolume extends SoundDataUpdatePayload{
	public const ID = SoundDataUpdateType::SET_VOLUME;

	public function __construct(
		private float $volume,
	){}

	public function getTypeId() : int{
		return self::ID;
	}

	public function getVolume() : float{ return $this->volume; }

	public static function read(ByteBufferReader $in) : self{
		return new self(
			LE::readFloat($in),
		);
	}

	public function write(ByteBufferWriter $out) : void{
		LE::writeFloat($out, $this->volume);
	}
}
