<?php

declare(strict_types = 1);

namespace JavierLeon9966\Redstone\utils;

use pocketmine\block\Block;
use pocketmine\math\Vector3;

final class Utils{
    private function __construct(){

    }

    public static function isNormalBlock(Block $block): bool
    {
        return !$block->isTransparent() && $block->isSolid() && !RedstoneUtils::isPowerSource($block);
    }

    public static function isSideFull(Block $block, int $face): bool
    {
        $boundingBox = $block->getBoundingBox();
        if ($boundingBox === null) return false;

        if (!in_array($face, [Vector3::SIDE_UP, Vector3::SIDE_DOWN])) {
            if ($boundingBox->minY !== $block->y || $boundingBox->maxY !== $block->y + 1) return false;
            $offset = [
                Vector3::SIDE_EAST => 1,
                Vector3::SIDE_WEST => -1
            ][$face] ?? 0;
            if ($offset < 0) {
                return $boundingBox->minX === $block->x
                    && $boundingBox->minZ === $block->z && $boundingBox->maxZ === $block->z + 1;
            } else if ($offset > 0){
                return $boundingBox->maxX === $block->x + 1
                    && $boundingBox->maxZ === $block->z + 1 && $boundingBox->minZ === $block->z;
            }

            $offset = [
                Vector3::SIDE_NORTH => -1,
                Vector3::SIDE_SOUTH => 1
            ][$face];

            return $offset < 0 ?
                ($boundingBox->minZ === $block->z
                    && $boundingBox->minX === $block->x && $boundingBox->maxX === $block->x + 1) : 
                ($boundingBox->maxZ === $block->z + 1
                    && $boundingBox->maxX === $block->x + 1 && $boundingBox->minX === $block->x);
        }

        if ($boundingBox->minX !== $block->x || $boundingBox->maxX !== $block->x + 1 || 
            $boundingBox->minZ !== $block->z || $boundingBox->maxZ !== $block->z + 1) return false;

        $offset = [
            Vector3::SIDE_DOWN => -1,
            Vector3::SIDE_UP => 1
        ][$face];
        
        return $offset < 0 ? $boundingBox->minY === $block->y : $boundingBox->maxY === $block->y + 1;
    }
}
