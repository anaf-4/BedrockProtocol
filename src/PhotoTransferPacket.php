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
use pmmp\encoding\LE;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use function preg_match;
use function strlen;

/**
 * r/26_u4 (protocol 2169)부터 다음 검증이 추가됨:
 * - Photo Name은 "[uuid].jpeg" 형식이어야 함
 * - Photo Data는 최대 20MiB(20971520바이트)로 제한됨
 *
 * 참고: Mojang bedrock-protocol-docs, changelog_2168_07_07_26.md (r/26_u4)
 */
class PhotoTransferPacket extends DataPacket implements ClientboundPacket{
	public const NETWORK_ID = ProtocolInfo::PHOTO_TRANSFER_PACKET;

	private const PHOTO_NAME_PATTERN = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}\.jpeg$/';
	private const MAX_PHOTO_DATA_SIZE = 20971520; //20 MiB

	public string $photoName;
	public string $photoData;
	public string $bookId; //photos are stored in a sibling directory to the games folder (screenshots/(some UUID)/bookID/example.png)
	public int $type;
	public int $sourceType;
	public int $ownerActorUniqueId;
	public string $newPhotoName; //???

	/**
	 * @generate-create-func
	 */
	public static function create(
		string $photoName,
		string $photoData,
		string $bookId,
		int $type,
		int $sourceType,
		int $ownerActorUniqueId,
		string $newPhotoName,
	) : self{
		self::validate($photoName, $photoData);
		$result = new self;
		$result->photoName = $photoName;
		$result->photoData = $photoData;
		$result->bookId = $bookId;
		$result->type = $type;
		$result->sourceType = $sourceType;
		$result->ownerActorUniqueId = $ownerActorUniqueId;
		$result->newPhotoName = $newPhotoName;
		return $result;
	}

	private static function validate(string $photoName, string $photoData) : void{
		if(preg_match(self::PHOTO_NAME_PATTERN, $photoName) !== 1){
			throw new PacketDecodeException("Photo name \"$photoName\" does not match the required [uuid].jpeg pattern");
		}
		if(strlen($photoData) > self::MAX_PHOTO_DATA_SIZE){
			throw new PacketDecodeException("Photo data exceeds maximum size of " . self::MAX_PHOTO_DATA_SIZE . " bytes");
		}
	}

	protected function decodePayload(ByteBufferReader $in) : void{
		$this->photoName = CommonTypes::getString($in);
		$this->photoData = CommonTypes::getString($in);
		self::validate($this->photoName, $this->photoData);
		$this->bookId = CommonTypes::getString($in);
		$this->type = Byte::readUnsigned($in);
		$this->sourceType = Byte::readUnsigned($in);
		$this->ownerActorUniqueId = LE::readSignedLong($in); //...............
		$this->newPhotoName = CommonTypes::getString($in);
	}

	protected function encodePayload(ByteBufferWriter $out) : void{
		CommonTypes::putString($out, $this->photoName);
		CommonTypes::putString($out, $this->photoData);
		CommonTypes::putString($out, $this->bookId);
		Byte::writeUnsigned($out, $this->type);
		Byte::writeUnsigned($out, $this->sourceType);
		LE::writeSignedLong($out, $this->ownerActorUniqueId);
		CommonTypes::putString($out, $this->newPhotoName);
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handlePhotoTransfer($this);
	}
}