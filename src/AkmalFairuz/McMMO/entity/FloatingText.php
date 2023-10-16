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

use pocketmine\player\Player;

use pocketmine\nbt\tag\CompoundTag;

use pocketmine\network\mcpe\protocol\SetActorDataPacket;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataTypes;

use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\utils\TextFormat;

use AkmalFairuz\McMMO\Main;
use pocketmine\network\mcpe\protocol\types\entity\StringMetadataProperty;

class FloatingText extends Human {

    public int $updateTick = 0;

    public int $type = 0;

    public function getName() : string {
	return "FloatingText";
    }

    public function initEntity(CompoundTag $nbt) : void {
	parent::initEntity($nbt);
	$this->setNameTagAlwaysVisible();
	$this->setScale(1);
	$this->updateTick = 0;
        $this->type = $nbt->getInt("type");
    }

    public function onUpdate(int $currentTick) : bool {
        parent::onUpdate($currentTick);
        $this->setNoClientPredictions();
	$this->updateTick++;
        if($this->updateTick == 20) {
            $this->updateTick = 0;
            $a = ["Lumberjack", "Farmer", "Excavation", "Miner", "Killer", "Combat", "Builder", "Consumer", "Archer", "Lawn Mower"];
            $l = "";
            $i = 0;
            $lead = Main::getInstance()->getAll($this->type);
            arsort($lead);
            foreach($lead as $k => $o) {
                if($i == 20) break;
                $i++;
                $l .= TextFormat::RED. $i . ") " . TextFormat::GREEN . $k . TextFormat::RED . " : " . TextFormat::BLUE . "Lv. " . $o . "\n";
            }
            $this->setNameTag(TextFormat::BOLD . TextFormat::AQUA . "MCMMO Leaderboard\n" . TextFormat::RESET . TextFormat::YELLOW . $a[$this->type] . TextFormat::RESET . "\n\n".$l);
            foreach ($this->getViewers() as $player) {
                $this->sendNameTag($player);
            }
        }
        return true; 
    }

    public function sendNameTag(Player $player): void {
        $pk = new SetActorDataPacket();
        $pk->actorRuntimeId = $this->getId();
        $pk->metadata = [EntityMetadataProperties::NAMETAG =>  new StringMetadataProperty($this->getNameTag())];
        $player->getNetworkSession()->sendDataPacket($pk);
    }

    public function attack(EntityDamageEvent $source) : void {
    	$source->cancel();
    }
}
