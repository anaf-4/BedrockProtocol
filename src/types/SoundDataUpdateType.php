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

/**
 * @see SoundDataUpdatePayload
 */
final class SoundDataUpdateType{
	public const STOP = 1;
	public const SET_VOLUME = 2;
	public const SET_PITCH = 3;
	public const FADE = 4;
	public const SEEK_TO = 5;
	public const PAUSE = 6;
	public const RESUME = 7;
}
