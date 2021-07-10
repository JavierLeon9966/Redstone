<?php

declare(strict_types = 1);

namespace JavierLeon9966\Redstone\block;

use JavierLeon9966\Redstone\utils\{RedstoneComponent, RedstoneUtils, Utils};

use pocketmine\block\Block;
use pocketmine\block\Flowable;
use pocketmine\block\Slab;
use pocketmine\block\Stair;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\Player;

class RedstoneWireBlock extends Flowable
{
    use RedstoneTrait;

    protected $id = self::REDSTONE_WIRE;
    protected $itemId = Item::REDSTONE;

    private $canProvidePower = true;

    public function __construct(int $meta = 0)
    {
        $this->meta = $meta;
    }

    public function getName(): string
    {
        return 'Redstone Wire';
    }

    public function place(Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, Player $player = null): bool
    {
        if (!$this->canBePlacedOn($this->down())) return false;

        $this->getLevelNonNull()->setBlock($blockReplace, $this, true);

        $this->updateSurroundingRedstone(true);

        foreach ([Vector3::SIDE_DOWN, Vector3::SIDE_UP] as $blockFace) {
            RedstoneComponent::updateAroundRedstone($this->getSide($blockFace), [Vector3::getOppositeSide($blockFace)]);
        }

        foreach ([Vector3::SIDE_DOWN, Vector3::SIDE_UP] as $blockFace) {
            $this->updateAround($this->getSide($blockFace), Vector3::getOppositeSide($blockFace));
        }

        foreach ($this->getHorizontalSides() as $block) {
            if (Utils::isNormalBlock($block)) $this->updateAround($block->up(), Vector3::SIDE_DOWN);
            else $this->updateAround($block->down(), Vector3::SIDE_UP);
        }

        return true;
    }

    private function updateAround(Block $block, int $face): void
    {
        if ($block->getId() === self::REDSTONE_WIRE) {
            RedstoneComponent::updateAroundRedstone($this, [$face]);

            foreach ($this->getAllSides() as $side => $sideBlock) {
                RedstoneComponent::updateAroundRedstone($sideBlock, [Vector3::getOppositeSide($side)]);
            }
        }
    }

    private function updateSurroundingRedstone(bool $force): void
    {
        $this->calculateCurrentChanges($force);
    }

    private function calculateCurrentChanges(bool $force): void
    {
        $maxStrength = $this->meta;
        $this->canProvidePower = false;
        $power = $this->getIndirectPower();

        $this->canProvidePower = true;

        if ($power > 0 && $power > $maxStrength - 1) $maxStrength = $power;

        $strength = 0;

        foreach ($this->getHorizontalSides() as $block) {
            if ($block->x === $this->x && $block->z === $this->z) continue;

            $strength = $this->getMaxCurrentStrength($block, $strength);

            if ($this->getMaxCurrentStrength($block->up(), $strength) > $strength && !Utils::isNormalBlock($this->up())) {
                $strength = $this->getMaxCurrentStrength($block->up(), $strength);
            }
            if ($this->getMaxCurrentStrength($block->down(), $strength) > $strength && !Utils::isNormalBlock($block)) {
                $strength = $this->getMaxCurrentStrength($block->down(), $strength);
            }
        }

        if ($strength > $maxStrength) $maxStrength = $strength - 1;
        else if ($maxStrength > 0) --$maxStrength;
        else $maxStrength = 0;

        if ($power > $maxStrength - 1) $maxStrength = $power;
        else if ($power < $maxStrength && $strength <= $maxStrength) $maxStrength = max($power, $strength - 1);

        if ($this->meta !== $maxStrength) {
            $this->meta = $maxStrength;
            $this->getLevelNonNull()->setBlock($this, $this, false, true);

            RedstoneComponent::updateAllAroundRedstone($this);
        } else if ($force) {
            foreach ($this->getAllSides() as $face => $block) {
                RedstoneComponent::updateAroundRedstone($block, [Vector3::getOppositeSide($face)]);
            }
        }
    }

    private function getMaxCurrentStrength(Block $block, int $maxStrength): int
    {
        return $block->getId() !== $this->getId() ? $maxStrength : max($block->meta, $maxStrength);
    }

    public function onBreak(Item $item, Player $player = null): bool
    {
        parent::onBreak($item, $player);

        $this->updateSurroundingRedstone(false);

        foreach ($this->getAllSides() as $block) RedstoneComponent::updateAroundRedstone($block);

        foreach ($this->getHorizontalSides() as $block) {
            if (Utils::isNormalBlock($block)) $this->updateAround($block->up(), Vector3::SIDE_DOWN);
            else $this->updateAround($block->down(), Vector3::SIDE_UP);
        }
        return true;
    }

    public function onNearbyBlockChange(): void
    {
        if(!$this->canBePlacedOn($this->down())){
            $this->getLevelNonNull()->useBreakOn($this);
            return;
        }

        $this->onRedstoneUpdate();
    }

    public function onRedstoneUpdate(): void
    {
        $this->updateSurroundingRedstone(false);
    }

    public function canBePlacedOn(Block $support): bool
    {
   	    return Utils::isSideFull($support, Vector3::SIDE_UP);
    }

    public function getStrongPower(int $side): int
    {
        return !$this->canProvidePower ? 0 : $this->getWeakPower($side);
    }

    public function getWeakPower(int $side): int
    {
        if (!$this->canProvidePower) return 0;
        else {
            $power = $this->meta;

            if ($power === 0) return 0;
            else if ($side === Vector3::SIDE_UP) return $power;
            else {
                $faces = [];

                foreach ([Vector3::SIDE_NORTH, Vector3::SIDE_SOUTH, Vector3::SIDE_WEST, Vector3::SIDE_EAST] as $face) {
                    if ($this->isPowerSourceAt($face)) $faces[] = $face;
                }

                $rotateYCCW = [
                    Vector3::SIDE_NORTH => Vector3::SIDE_WEST,
                    Vector3::SIDE_EAST => Vector3::SIDE_NORTH,
                    Vector3::SIDE_SOUTH => Vector3::SIDE_EAST,
                    Vector3::SIDE_WEST => Vector3::SIDE_SOUTH
                ];
                $rotateY = array_flip($rotateYCCW);

                return (
                    ($side !== Vector3::SIDE_DOWN && count($faces) === 0) ||
                    (in_array($side, $faces, true) && !in_array($rotateYCCW[$side], $faces, true) && !in_array($rotateY[$side], $faces, true))
                ) ? $power : 0;
            }
        }
    }

    private function isPowerSourceAt(int $side): bool
    {
        $block = $this->getSide($side);
        $flag = Utils::isNormalBlock($block);
        $flag1 = Utils::isNormalBlock($block->up());
        return !$flag1 && $flag && self::canConnectTo($block->up()) || (self::canConnectTo($block, $side) || !$flag && self::canConnectTo($block->down()));
    }

    protected static function canConnectTo(Block $block, ?int $side = null): bool
    {
        if ($block->getId() === self::REDSTONE_WIRE) return true;
        else if (RedstoneDiodeBlock::isDiode($block)) {
            $face = $block->getFacing();
            return $face === $side || Vector3::getOppositeSide($face) === $side;
        } else return RedstoneUtils::isPowerSource($block) && $side !== null;
    }

    public function isPowerSource(): bool
    {
        return $this->canProvidePower;
    }

    private function getIndirectPower(): int
    {
        $power = 0;

        foreach ($this->getAllSides() as $face => $block) {
            $blockPower = 0;

            if ($block->getId() !== self::REDSTONE_WIRE) {
                if (Utils::isNormalBlock($block)) {
                    foreach ($block->getAllSides() as $side => $sideBlock) {
                        $blockPower = max($blockPower, $sideBlock->getId() === self::REDSTONE_WIRE ? 0 : RedstoneUtils::getStrongPower($sideBlock, $side));

                        if ($blockPower >= 15) return 15;
                    }
                } else $blockPower = RedstoneUtils::getWeakPower($block, $face);
            }

            if ($blockPower >= 15) return 15;

            if ($blockPower > $power) $power = $blockPower;
        }

        return $power;
    }

    public function getVariantBitmask(): int
    {
        return 0;
    }
}
