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
use pocketmine\color\Color;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\skin\SkinData;
use Ramsey\Uuid\UuidInterface;

/**
 * @see PlayerListPayloadEntry
 * @see \pocketmine\network\mcpe\protocol\PlayerListPacket
 */
final class PlayerListEntryAdd extends PlayerListPayloadEntry{
	public const ID = 0; //TYPE_ADD, matches legacy packet-level convention

	public function __construct(
		UuidInterface $uuid,
		private int $actorUniqueId,
		private string $username,
		private string $xboxUserId,
		private string $platformChatId,
		private int $buildPlatform,
		private SkinData $skinData,
		private bool $isTeacher,
		private bool $isHost,
		private bool $isSubClient,
		private Color $color,
	){
		parent::__construct($uuid);
	}

	public function getActorUniqueId() : int{ return $this->actorUniqueId; }

	public function getUsername() : string{ return $this->username; }

	public function getXboxUserId() : string{ return $this->xboxUserId; }

	public function getPlatformChatId() : string{ return $this->platformChatId; }

	public function getBuildPlatform() : int{ return $this->buildPlatform; }

	public function getSkinData() : SkinData{ return $this->skinData; }

	public function isTeacher() : bool{ return $this->isTeacher; }

	public function isHost() : bool{ return $this->isHost; }

	public function isSubClient() : bool{ return $this->isSubClient; }

	public function getColor() : Color{ return $this->color; }

	public function getActionId() : int{
		return self::ID;
	}

	/**
	 * Action 바이트는 외부(PlayerListPacket::decodePayload)에서 이미 읽음.
	 */
	public static function read(ByteBufferReader $in) : self{
		$uuid = CommonTypes::getUUID($in);
		$actorUniqueId = CommonTypes::getActorUniqueId($in);
		$username = CommonTypes::getString($in);
		$xboxUserId = CommonTypes::getString($in);
		$platformChatId = CommonTypes::getString($in);
		$buildPlatform = LE::readSignedInt($in);
		$skinData = CommonTypes::getSkin($in);
		$isTeacher = CommonTypes::getBool($in);
		$isHost = CommonTypes::getBool($in);
		$isSubClient = CommonTypes::getBool($in);
		$color = Color::fromARGB(LE::readUnsignedInt($in));
		return new self($uuid, $actorUniqueId, $username, $xboxUserId, $platformChatId, $buildPlatform, $skinData, $isTeacher, $isHost, $isSubClient, $color);
	}

	public function write(ByteBufferWriter $out) : void{
		CommonTypes::putUUID($out, $this->getUuid());
		CommonTypes::putActorUniqueId($out, $this->actorUniqueId);
		CommonTypes::putString($out, $this->username);
		CommonTypes::putString($out, $this->xboxUserId);
		CommonTypes::putString($out, $this->platformChatId);
		LE::writeSignedInt($out, $this->buildPlatform);
		CommonTypes::putSkin($out, $this->skinData);
		CommonTypes::putBool($out, $this->isTeacher);
		CommonTypes::putBool($out, $this->isHost);
		CommonTypes::putBool($out, $this->isSubClient);
		LE::writeUnsignedInt($out, $this->color->toARGB());
	}
}