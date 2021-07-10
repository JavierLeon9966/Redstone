<?php

declare(strict_types = 1);

namespace JavierLeon9966\Redstone\utils;

use pocketmine\block\Block;
use pocketmine\math\Vector3;

final class RedstoneComponent
{
    private function __construct(){

    }

    public static function updateAroundRedstone(Block $block, array $ignoredFaces = []): void
    {
        foreach ($block->getAllSides() as $face => $sideBlock) {
            if(in_array($face, $ignoredFaces, true)) continue;

            RedstoneUtils::onRedstoneUpdate($sideBlock);
        }
    }

    public static function updateAllAroundRedstone(Block $block, array $ignoredFaces = []): void
    {
        self::updateAroundRedstone($block, $ignoredFaces);

        foreach ($block->getAllSides() as $face => $sideBlock) {
            if (in_array($face, $ignoredFaces, true)) continue;

            self::updateAroundRedstone($sideBlock, [Vector3::getOppositeSide($face)]);
        }
    }
}