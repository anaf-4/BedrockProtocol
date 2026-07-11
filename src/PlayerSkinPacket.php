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
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\skin\SkinData;
use Ramsey\Uuid\UuidInterface;

/**
 * r/26_u4 (protocol 2169)부터 스킨 검증(verified/trusted) 플래그가 패킷 레벨의
 * 별도 trailing bool이 아니라, Serialized Skin(TrustedSkinFlag) 자체 인코딩에
 * 포함되도록 바뀜 (CommonTypes::getSkin/putSkin에서 처리).
 *
 * 참고: Mojang bedrock-protocol-docs, changelog_2168_07_07_26.md (r/26_u4)
 */
class PlayerSkinPacket extends DataPacket implements ClientboundPacket, ServerboundPacket{
	public const NETWORK_ID = ProtocolInfo::PLAYER_SKIN_PACKET;

	public UuidInterface $uuid;
	public string $oldSkinName = "";
	public string $newSkinName = "";
	public SkinData $skin;

	/**
	 * @generate-create-func
	 */
	public static function create(UuidInterface $uuid, string $oldSkinName, string $newSkinName, SkinData $skin) : self{
		$result = new self;
		$result->uuid = $uuid;
		$result->oldSkinName = $oldSkinName;
		$result->newSkinName = $newSkinName;
		$result->skin = $skin;
		return $result;
	}

	protected function decodePayload(ByteBufferReader $in) : void{
		$this->uuid = CommonTypes::getUUID($in);
		$this->skin = CommonTypes::getSkin($in);
		$this->newSkinName = CommonTypes::getString($in);
		$this->oldSkinName = CommonTypes::getString($in);
	}

	protected function encodePayload(ByteBufferWriter $out) : void{
		CommonTypes::putUUID($out, $this->uuid);
		CommonTypes::putSkin($out, $this->skin);
		CommonTypes::putString($out, $this->newSkinName);
		CommonTypes::putString($out, $this->oldSkinName);
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handlePlayerSkin($this);
	}
}