<?php

declare(strict_types = 1);

namespace JavierLeon9966\Redstone\block;

use pocketmine\block\Block;
use pocketmine\math\Vector3;

trait RedstoneTrait
{
    public function updateRedstone(Block $block, int $face = null): void
    {
        foreach ($block->getAllSides() as $side => $sideBlock) {
            if ($face !== null && $face === $side) continue;

            if ($sideBlock instanceof RedstoneInterface) $sideBlock->onRedstoneUpdate();
        }
    }

    public function updateUnpoweredRedstone(Block $block): void
    {
        $cash = [];
        foreach ($block->getAllSides() as $side => $sideBlock) {
            if (in_array($sideBlock, $cash, true)) continue;

            $cash[] = $sideBlock;

            foreach($sideBlock->getAllSides() as $side2 => $sideBlock2){
                if(Vector3::getOppositeSide($side) === $side2 || in_array($sideBlock2, $cash, true)) continue;

                $cash[] = $sideBlock2;
            }
        }
        foreach($cash as $block) if($block instanceof RedstoneInterface) $block->onRedstoneUpdate();
    }

    public function getRedstonePower(Block $block, int $face): int
    {
        return (!$block->isTransparent() && $block->isSolid() && (!$block instanceof RedstoneInterface || !$block->isPowerSource())) ? $this->getStrongPowered($block) : $block->getWeakPower($face);
    }

    public function isBlockPowered(Block $block, int $face = null): bool
    {
        foreach ($block->getAllSides() as $side => $sideBlock) {
            if ($face !== null && $face === $side) continue;

            if ($this->getRedstonePower($sideBlock, $side) > 0) return true;
        }
        return false;
    }

    public function isSidePowered(Block $block, int $face): bool
    {
        return $this->getRedstonePower($block->getSide($face), $face) > 0;
    }

    public function getStrongPowered(Block $block): int
    {
        $power = 0;
        foreach ($block->getAllSides() as $side => $sideBlock) {
            $power = max($power, $this->getSideStrongPowered($sideBlock, $side));

            if ($power >= 15) return $power;
        }
        return $power;
    }

    public function getSideStrongPowered(Block $block, int $face): int
    {
        return $block instanceof RedstoneInterface ? $block->getStrongPower($face) : 0;
    }
}