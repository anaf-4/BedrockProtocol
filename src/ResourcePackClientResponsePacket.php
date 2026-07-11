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
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use function count;

/**
 * r/26_u4 (protocol 2169)부터: Downloading Packs 목록이 이제 Response Type이
 * "Downloading"(2)일 때만 존재함 (레거시에서는 모든 상태에서 항상 배열을
 * 무조건 직렬화했음, 대부분 빈 배열). 개수 prefix도 레거시 uint16 LE 대신
 * VarInt로 바뀜.
 *
 * 참고: Mojang bedrock-protocol-docs, changelog_2168_07_07_26.md (r/26_u4)
 * STATUS_* 값(1~4)은 실제 프로토콜 값으로, 레거시 구현에서 그대로 가져옴.
 */
class ResourcePackClientResponsePacket extends DataPacket implements ServerboundPacket{
	public const NETWORK_ID = ProtocolInfo::RESOURCE_PACK_CLIENT_RESPONSE_PACKET;

	public const STATUS_REFUSED = 1; //Cancel
	public const STATUS_SEND_PACKS = 2; //Downloading
	public const STATUS_HAVE_ALL_PACKS = 3; //DownloadingFinished
	public const STATUS_COMPLETED = 4; //ResourcePackStackFinished

	public int $status;
	/** @var string[] */
	public array $packIds = [];

	/**
	 * @generate-create-func
	 * @param string[] $packIds
	 */
	public static function create(int $status, array $packIds) : self{
		$result = new self;
		$result->status = $status;
		$result->packIds = $packIds;
		return $result;
	}

	protected function decodePayload(ByteBufferReader $in) : void{
		$this->status = Byte::readUnsigned($in);
		$this->packIds = [];
		if($this->status === self::STATUS_SEND_PACKS){
			for($i = 0, $count = VarInt::readUnsignedInt($in); $i < $count; $i++){
				$this->packIds[] = CommonTypes::getString($in);
			}
		}
	}

	protected function encodePayload(ByteBufferWriter $out) : void{
		Byte::writeUnsigned($out, $this->status);
		if($this->status === self::STATUS_SEND_PACKS){
			VarInt::writeUnsignedInt($out, count($this->packIds));
			foreach($this->packIds as $id){
				CommonTypes::putString($out, $id);
			}
		}
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handleResourcePackClientResponse($this);
	}
}