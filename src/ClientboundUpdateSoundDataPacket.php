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
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\types\SoundDataUpdateFade;
use pocketmine\network\mcpe\protocol\types\SoundDataUpdatePause;
use pocketmine\network\mcpe\protocol\types\SoundDataUpdatePayload;
use pocketmine\network\mcpe\protocol\types\SoundDataUpdateResume;
use pocketmine\network\mcpe\protocol\types\SoundDataUpdateSeekTo;
use pocketmine\network\mcpe\protocol\types\SoundDataUpdateSetPitch;
use pocketmine\network\mcpe\protocol\types\SoundDataUpdateSetVolume;
use pocketmine\network\mcpe\protocol\types\SoundDataUpdateStop;

/**
 * r/26_u4 (protocol 2169)부터 완전히 재설계됨: 기존에는 (serverSoundHandle, soundEvent)
 * 단순 구조였으나, 이제는 서버가 재생 중인 사운드 핸들을 제어하는 명령
 * (Stop/SetVolume/SetPitch/Fade/SeekTo/Pause/Resume) 중 하나를 담는 태그드 유니온 구조.
 *
 * 참고: Mojang bedrock-protocol-docs, changelog_2168_07_07_26.md (r/26_u4)
 */
class ClientboundUpdateSoundDataPacket extends DataPacket implements ClientboundPacket{
	public const NETWORK_ID = ProtocolInfo::CLIENTBOUND_UPDATE_SOUND_DATA_PACKET;

	private int $serverSoundHandle;
	private SoundDataUpdatePayload $payload;

	/**
	 * @generate-create-func
	 */
	public static function create(int $serverSoundHandle, SoundDataUpdatePayload $payload) : self{
		$result = new self;
		$result->serverSoundHandle = $serverSoundHandle;
		$result->payload = $payload;
		return $result;
	}

	public function getServerSoundHandle() : int{ return $this->serverSoundHandle; }

	public function getPayload() : SoundDataUpdatePayload{ return $this->payload; }

	protected function decodePayload(ByteBufferReader $in) : void{
		$this->serverSoundHandle = LE::readUnsignedLong($in);
		$this->payload = match(VarInt::readUnsignedInt($in)){
			SoundDataUpdateStop::ID => SoundDataUpdateStop::read($in),
			SoundDataUpdateSetVolume::ID => SoundDataUpdateSetVolume::read($in),
			SoundDataUpdateSetPitch::ID => SoundDataUpdateSetPitch::read($in),
			SoundDataUpdateFade::ID => SoundDataUpdateFade::read($in),
			SoundDataUpdateSeekTo::ID => SoundDataUpdateSeekTo::read($in),
			SoundDataUpdatePause::ID => SoundDataUpdatePause::read($in),
			SoundDataUpdateResume::ID => SoundDataUpdateResume::read($in),
			default => throw new PacketDecodeException("Unknown ClientboundUpdateSoundData payload type"),
		};
	}

	protected function encodePayload(ByteBufferWriter $out) : void{
		LE::writeUnsignedLong($out, $this->serverSoundHandle);
		VarInt::writeUnsignedInt($out, $this->payload->getTypeId());
		$this->payload->write($out);
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handleClientboundUpdateSoundData($this);
	}
}
