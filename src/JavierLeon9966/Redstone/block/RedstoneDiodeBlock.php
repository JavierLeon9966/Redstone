<?php

declare(strict_types = 1);

namespace JavierLeon9966\Redstone\block;

use pocketmine\block\Block;

abstract class RedstoneDiodeBlock
{
    abstract public function getFacing(): int;

    public static function isDiode(Block $block): bool
    {
        return $block instanceof self;
    }
}
