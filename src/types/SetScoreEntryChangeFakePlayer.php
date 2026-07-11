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
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

/**
 * @see SetScorePacket
 */
final class SetScoreEntryChangeFakePlayer extends SetScorePayloadEntry{
	public const ID = ScorePacketEntryActionType::CHANGE_FAKE_PLAYER;

	public function __construct(
		int $scoreboardId,
		private string $objectiveName,
		private int $scoreValue,
		private string $fakePlayerName,
	){
		parent::__construct($scoreboardId);
	}

	public function getActionId() : int{
		return self::ID;
	}

	public function getObjectiveName() : string{ return $this->objectiveName; }

	public function getScoreValue() : int{ return $this->scoreValue; }

	public function getFakePlayerName() : string{ return $this->fakePlayerName; }

	public static function read(ByteBufferReader $in) : self{
		$scoreboardId = VarInt::readSignedLong($in);
		$objectiveName = CommonTypes::getString($in);
		$scoreValue = LE::readSignedInt($in);
		$fakePlayerName = CommonTypes::getString($in);
		return new self($scoreboardId, $objectiveName, $scoreValue, $fakePlayerName);
	}

	public function write(ByteBufferWriter $out) : void{
		VarInt::writeSignedLong($out, $this->getScoreboardId());
		CommonTypes::putString($out, $this->objectiveName);
		LE::writeSignedInt($out, $this->scoreValue);
		CommonTypes::putString($out, $this->fakePlayerName);
	}
}
