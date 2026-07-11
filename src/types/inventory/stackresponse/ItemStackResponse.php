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
namespace pocketmine\network\mcpe\protocol\types\inventory\stackresponse;

use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use function count;

/**
 * r/26_u4 (protocol 2169)부터 Containers가 명시적인 optional로 인코딩됨:
 * presence bool이 항상 먼저 쓰여지고, Result == Success일 때만 그 값(배열)이
 * 뒤따름 (기존에는 result==OK일 때 곧바로 배열 길이를 읽었는데, 이제 그 앞에
 * presence bool이 하나 더 붙음).
 *
 * 또한 Result enum이 60개 이상의 세부 에러 코드로 크게 확장됐으나, 지금 당장은
 * 기존처럼 RESULT_OK/RESULT_ERROR 두 상수만 유지합니다(전체 목록은 필요할 때 추가).
 *
 * 참고: Mojang bedrock-protocol-docs, changelog_2168_07_07_26.md (r/26_u4)
 */
final class ItemStackResponse{
	public const RESULT_OK = 0;
	public const RESULT_ERROR = 1;
	//TODO: there are a ton more possible result types but we don't need them yet and they are wayyyyyy too many for me
	//to waste my time on right now...

	/**
	 * @param ItemStackResponseContainerInfo[] $containerInfos
	 */
	public function __construct(
		private int $result,
		private int $requestId,
		private array $containerInfos = []
	){
		if($this->result !== self::RESULT_OK && count($this->containerInfos) !== 0){
			throw new \InvalidArgumentException("Container infos must be empty if rejecting the request");
		}
	}

	public function getResult() : int{ return $this->result; }

	public function getRequestId() : int{ return $this->requestId; }

	/** @return ItemStackResponseContainerInfo[] */
	public function getContainerInfos() : array{ return $this->containerInfos; }

	public static function read(ByteBufferReader $in) : self{
		$result = Byte::readUnsigned($in);
		$requestId = CommonTypes::readItemStackRequestId($in);
		$containerInfos = CommonTypes::readOptional($in, function() use ($in) : array{
			$list = [];
			for($i = 0, $len = VarInt::readUnsignedInt($in); $i < $len; ++$i){
				$list[] = ItemStackResponseContainerInfo::read($in);
			}
			return $list;
		}) ?? [];
		return new self($result, $requestId, $containerInfos);
	}

	public function write(ByteBufferWriter $out) : void{
		Byte::writeUnsigned($out, $this->result);
		CommonTypes::writeItemStackRequestId($out, $this->requestId);
		$hasContainers = $this->result === self::RESULT_OK;
		CommonTypes::writeOptional($out, $hasContainers ? $this->containerInfos : null, function(ByteBufferWriter $out, array $containerInfos) : void{
			VarInt::writeUnsignedInt($out, count($containerInfos));
			foreach($containerInfos as $containerInfo){
				$containerInfo->write($out);
			}
		});
	}
}