<?php

declare(strict_types = 1);

namespace JavierLeon9966\Redstone\utils;

use pocketmine\block\Block;

final class RedstoneUtils
{
    private function __construct(){

    }

    public static function getStrongPower(Block $block, int $side): int
    {
        return method_exists($block, 'getStrongPower') ? $block->getStrongPower($side) : 0;
    }

    public static function getWeakPower(Block $block, int $side): int
    {
        return method_exists($block, 'getWeakPower') ? $block->getWeakPower($side) : 0;
    }

    public static function isPowerSource(Block $block): bool
    {
        return method_exists($block, 'isPowerSource') && $block->isPowerSource();
    }

    public static function onRedstoneUpdate(Block $block): void
    {
        if(method_exists($block, 'onRedstoneUpdate')) $block->onRedstoneUpdate();
    }
}