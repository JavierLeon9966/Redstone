<?php

declare(strict_types = 1);

namespace JavierLeon9966\Redstone\block;

trait RedstoneTrait
{
    public function onRedstoneUpdate(): void
    {

    }
    
    public function getWeakPower(int $side): int
    {
        return 0;
    }

    public function getStrongPower(int $side): int
    {
        return 0;
    }

    public function isPowerSource(): bool
    {
        return false;
    }
}