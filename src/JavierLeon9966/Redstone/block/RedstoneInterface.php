<?php

declare(strict_types = 1);

namespace JavierLeon9966\Redstone\block;

interface RedstoneInterface
{
    function getStrongPower(int $face): int;

    function getWeakPower(int $face): int;

    function isPowerSource(): bool;

    function onRedstoneUpdate(): void;
}
