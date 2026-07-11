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
use function preg_match;
use function strlen;

/**
 * r/26_u4 (protocol 2169)부터 직렬화된 토큰에 크기 제약(16 KiB)이 추가되고,
 * 올바른 형식의 JWT인지 검증하도록 바뀜.
 *
 * 참고: Mojang bedrock-protocol-docs, changelog_2168_07_07_26.md (r/26_u4)
 */
class ServerToClientHandshakePacket extends DataPacket implements ClientboundPacket{
	public const NETWORK_ID = ProtocolInfo::SERVER_TO_CLIENT_HANDSHAKE_PACKET;

	private const MAX_JWT_SIZE = 16384; //16 KiB

	//대략적인 JWT 형식 검사: base64url 세그먼트 3개(헤더.페이로드.서명)로 구성되는지만 확인.
	//서명 검증 등 완전한 JWT 유효성 검사는 아님 - 인코딩 계층에서 형식만 확인.
	private const JWT_FORMAT_PATTERN = '/^[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+$/';

	/** Server pubkey and token is contained in the JWT. */
	public string $jwt;

	/**
	 * @generate-create-func
	 */
	public static function create(string $jwt) : self{
		self::validate($jwt);
		$result = new self;
		$result->jwt = $jwt;
		return $result;
	}

	private static function validate(string $jwt) : void{
		if(strlen($jwt) > self::MAX_JWT_SIZE){
			throw new PacketDecodeException("Handshake token exceeds maximum size of " . self::MAX_JWT_SIZE . " bytes");
		}
		if(preg_match(self::JWT_FORMAT_PATTERN, $jwt) !== 1){
			throw new PacketDecodeException("Handshake token is not a well-formed JWT");
		}
	}

	public function canBeSentBeforeLogin() : bool{
		return true;
	}

	protected function decodePayload(ByteBufferReader $in) : void{
		$this->jwt = CommonTypes::getString($in);
		self::validate($this->jwt);
	}

	protected function encodePayload(ByteBufferWriter $out) : void{
		CommonTypes::putString($out, $this->jwt);
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handleServerToClientHandshake($this);
	}
}