<?php

/*
 *
 *              _                             _        ______             _
 *     /\      | |                           | |      |  ____|           (_)
 *    /  \     | | __   _ __ ___      __ _   | |      | |__       __ _    _    _ __    _   _    ____
 *   / /\ \    | |/ /  | '_ ` _ \    / _` |  | |      |  __|     / _` |  | |  | '__|  | | | |  |_  /
 *  / ____ \   |   <   | | | | | |  | (_| |  | |      | |       | (_| |  | |  | |     | |_| |   / /
 * /_/    \_\  |_|\_\  |_| |_| |_|   \__,_|  |_|      |_|        \__,_|  |_|  |_|      \__,_|  /___|
 *
 * Discord: akmal#7191
 * GitHub: https://github.com/AkmalFairuz
 *
 */

namespace AkmalFairuz\McMMO\entity;

use pocketmine\entity\Human;
use pocketmine\entity\Ageable;
use pocketmine\entity\EntitySizeInfo;

use pocketmine\player\Player;

use pocketmine\nbt\tag\CompoundTag;

use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\utils\TextFormat;

use AkmalFairuz\McMMO\Main;

class FloatingText extends Human implements Ageable {

    private $baby = false;

    protected $gravity = 0;

    protected function getInitialSizeInfo() : EntitySizeInfo {
		return new EntitySizeInfo(0, 0);
	}

    public function getName() : string {
		return "FloatingText";
	}

    public function isBaby() : bool {
		return $this->baby;
    }

	public function initEntity(CompoundTag $nbt) : void {
		parent::initEntity($nbt);
		$this->setNameTagAlwaysVisible(true);
		$this->setScale(0.0000000000000000000000000000000001);
	}

	public function attack(EntityDamageEvent $source) : void {
		$source->cancel();
	}
}
