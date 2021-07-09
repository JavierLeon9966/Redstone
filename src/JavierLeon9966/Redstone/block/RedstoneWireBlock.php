<?php

declare(strict_types = 1);

namespace JavierLeon9966\Redstone\block;

use pocketmine\block\Block;
use pocketmine\block\BlockIds;
use pocketmine\block\Flowable;
use pocketmine\block\Slab;
use pocketmine\block\Stair;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\Player;

class RedstoneWireBlock extends Flowable implements RedstoneInterface
{
    use RedstoneTrait;

    public function __construct(int $meta = 0)
    {
        parent::__construct(BlockIds::REDSTONE_WIRE, $meta, "Redstone Wire");
    }

    public function getStrongPower(int $face): int
    {
        return 0;
    }

    public function place(Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, Player $player = null): bool
    {
        $under = $this->down();
        if (($under instanceof Stair && $under->getDamage() < 4) || ($under instanceof Slab && $under->getDamage() < 8) || !$under->isSolid() || $under->isTransparent()) return false;

        $this->updateUnpoweredRedstone($this);
        return parent::place($item, $blockReplace, $blockClicked, $face, $clickVector, $player);
    }

    public function onBreak(Item $item, Player $player = null): bool
    {
        $this->updateUnpoweredRedstone($this);
        return parent::onBreak($item,  $player);
    }

    public function onNearbyBlockChange(): void
    {
        $this->onRedstoneUpdate();

        $under = $this->down();

        if (($under instanceof Stair && $under->getDamage() >= 4) || ($under instanceof Slab && $under->getDamage() >= 8) || $under->isSolid() && !$under->isTransparent()) return;
        $this->level->useBreakOn($this);
    }

    public function getWeakPower(int $face): int
    {
        if ($face === Vector3::SIDE_UP) return $this->getDamage();
        if ($this->getSide(Vector3::getOppositeSide($face)) instanceof UnpoweredRedstoneBlock) return $this->getDamage();

        $directions = [Vector3::SIDE_NORTH, Vector3::SIDE_EAST, Vector3::SIDE_SOUTH, Vector3::SIDE_WEST];
        $directions = array_diff($directions, [$face, Vector3::getOppositeSide($face)]);
        foreach ($directions as $face) {
            $side = $this->getSide($face);
            if (($side instanceof RedstoneInterface && $side->isPowerSource()) || $side instanceof RedstoneWireBlock || $side instanceof UnpoweredRedstoneBlock) return 0;
        }
        return $this->getDamage();
    }

    public function onRedstoneUpdate(): void
    {
        $power = 0;

        foreach ($this->getAllSides() as $face => $block) {
            if ($block instanceof RedstoneWireBlock) $power = max($power, $block->getDamage() - 1);
            else if ($block instanceof RedstoneInterface && $block->isPowerSource()) $power = max($power, $block->getWeakPower($face));
            else if (!$block->isTransparent() && $block->isSolid() && (!$block instanceof RedstoneInterface || !$block->isPowerSource())) { // TODO: Add instance of Piston check once pistons are added
                foreach($block->getAllSides() as $side => $sideBlock){
                    if($side === Vector3::getOppositeSide($face)) continue;
                    if ($sideBlock instanceof RedstoneInterface && $sideBlock->isPowerSource()) $power = max($power, $sideBlock->getStrongPower($side));
                }
            } else if ($block->isTransparent()) {
                if ($face === Vector3::SIDE_UP) {
                    foreach($this->getHorizontalSides() as $sideBlock){
                        if ($sideBlock instanceof RedstoneWireBlock) $power = max($power, $sideBlock->getDamage() - 1);
                    }
                } else if ($face !== Vector3::SIDE_DOWN) {
                    $under = $block->down();
                    if ($under instanceof RedstoneWireBlock) $power = max($power, $under->getDamage() - 1);
                }
            }
        }

        if ($this->getDamage() !== $power) {
            $this->setDamage($power);
            $this->level->setBlock($this, $this, true);
            $this->updateUnpoweredRedstone($this);
        }
    }

    public function isPowerSource(): bool
    {
        return false;
    }

    public function getVariantBitmask(): int
    {
        return 0;
    }
}
