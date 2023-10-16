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

namespace AkmalFairuz\McMMO;

use pocketmine\block\BlockTypeIds;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;

use pocketmine\player\Player;

use pocketmine\event\Listener;

use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityShootBowEvent;
use pocketmine\event\player\PlayerItemConsumeEvent;
use pocketmine\event\player\PlayerLoginEvent;

use pocketmine\entity\EntityFactory;
use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\Human;
use pocketmine\entity\Location;

use pocketmine\nbt\tag\ByteArrayTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\StringTag;

use pocketmine\utils\SingletonTrait;
use pocketmine\world\World;

use pocketmine\block\Opaque;

use pocketmine\utils\TextFormat;

use AkmalFairuz\McMMO\command\McmmoCommand;
use AkmalFairuz\McMMO\command\McmmoSetupCommand;
use AkmalFairuz\McMMO\entity\FloatingText;

class Main extends PluginBase implements Listener {
    use SingletonTrait;

    public const LUMBERJACK = 0;
    public const FARMER = 1;
    public const MINER = 3;
    public const EXCAVATION = 2;
    public const COMBAT = 5;
    public const KILLER = 4;
    public const BUILDER = 6;
    public const CONSUMER = 7;
    public const ARCHER = 8;
    public const LAWN_MOWER = 9;


    /** @var array */
    public array $database;

    public function onEnable() : void {
        $this->saveResource("database.yml");
        $this->getServer()->getCommandMap()->register("mcmmo", new McmmoCommand());
        $this->getServer()->getCommandMap()->register("mcmmoadmin", new McmmoSetupCommand());
        $this->database = yaml_parse(file_get_contents($this->getDataFolder() . "database.yml"));
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        EntityFactory::getInstance()->register(FloatingText::class, function (World $world, CompoundTag $nbt) : FloatingText {
            return new FloatingText(EntityDataHelper::parseLocation($nbt, $world), Human::parseSkinNBT($nbt), $nbt);
        }, ["FloatingText", "FloatingTextEntity"]);
        self::setInstance($this);
    }

    public function onDisable() : void {
        file_put_contents($this->getDataFolder() . "database.yml", yaml_emit($this->database));
        // sleep(3); // save database delay
    }

    public function getXp(int $type, Player $player) : int {
        return $this->database["xp"][$type][strtolower($player->getName())];
    }

    public function getLevel(int $type, Player $player) : int {
        return $this->database["level"][$type][strtolower($player->getName())];
    }

    public function addXp(int $type, Player $player) {
        $this->database["xp"][$type][strtolower($player->getName())]++;
        if($this->database["xp"][$type][strtolower($player->getName())] >= ($this->getLevel($type, $player) * 100)) {
            $this->database["xp"][$type][strtolower($player->getName())] = 0;
            $this->addLevel($type, $player);
        }
        $a = ["Lumberjack", "Farmer", "Excavation", "Miner", "Killer", "Combat", "Builder", "Consumer", "Archer", "Lawn Mower"];
        $player->sendTip("Your McMMO ".$a[$type]." xp is ".$this->getXp($type, $player));
    }

    public function addLevel(int $type, Player $player) {
        $this->database["level"][$type][strtolower($player->getName())]++;
        $a = ["Lumberjack", "Farmer", "Excavation", "Miner", "Killer", "Combat", "Builder", "Consumer", "Archer", "Lawn Mower"];
        $player->sendMessage("Your McMMO ".$a[$type]." level is ".$this->getLevel($type, $player));
    }

    public function getAll(int $type) : array {
        return $this->database["level"][$type];
    }

    public function onLogin(PlayerLoginEvent $event) {
        $player = $event->getPlayer();
        if(!isset($this->database["xp"][0][strtolower($player->getName())])) {
            for($i = 0; $i < 10; $i++) {
                $this->database["xp"][$i][strtolower($player->getName())] = 0;
                $this->database["level"][$i][strtolower($player->getName())] = 1;
            }
        }
    }

    /**
     * @priority LOWEST
     */
    public function onBreak(BlockBreakEvent $event) {
        if($event->isCancelled()) {
            return;
        }
        $player = $event->getPlayer();
        $block = $event->getBlock();
        switch($block->getTypeId()) {
            case BlockTypeIds::WHEAT:
            case BlockTypeIds::BEETROOTS:
            case BlockTypeIds::PUMPKIN_STEM:
            case BlockTypeIds::PUMPKIN:
            case BlockTypeIds::MELON_STEM:
            case BlockTypeIds::MELON:
            case BlockTypeIds::CARROTS:
            case BlockTypeIds::POTATOES:
            case BlockTypeIds::SUGARCANE:
                $this->addXp(self::FARMER, $player);
                return;
            case BlockTypeIds::STONE:
            case BlockTypeIds::DIAMOND_ORE:
            case BlockTypeIds::GOLD_ORE:
            case BlockTypeIds::REDSTONE_ORE:
            case BlockTypeIds::IRON_ORE:
            case BlockTypeIds::COAL_ORE:
            case BlockTypeIds::EMERALD_ORE:
            case BlockTypeIds::OBSIDIAN:
                $this->addXp(self::MINER, $player);
                return;
            case BlockTypeIds::OAK_LOG:
            case BlockTypeIds::BIRCH_LOG:
            case BlockTypeIds::ACACIA_LOG:
            case BlockTypeIds::CHERRY_LOG:
            case BlockTypeIds::DARK_OAK_LOG:
            case BlockTypeIds::JUNGLE_LOG:
            case BlockTypeIds::MANGROVE_LOG:
            case BlockTypeIds::SPRUCE_LOG:
            case BlockTypeIds::OAK_LEAVES:
            case BlockTypeIds::BIRCH_LEAVES:
            case BlockTypeIds::ACACIA_LEAVES:
            case BlockTypeIds::CHERRY_LEAVES:
            case BlockTypeIds::DARK_OAK_LEAVES:
            case BlockTypeIds::JUNGLE_LEAVES:
            case BlockTypeIds::MANGROVE_LEAVES:
            case BlockTypeIds::SPRUCE_LEAVES:
                $this->addXp(self::LUMBERJACK, $player);
                return;
            case BlockTypeIds::DIRT:
            case BlockTypeIds::GRASS:
            case BlockTypeIds::GRASS_PATH:
            case BlockTypeIds::FARMLAND:
            case BlockTypeIds::SAND:
            case BlockTypeIds::GRAVEL:
                $this->addXp(self::EXCAVATION, $player);
                return;
            case BlockTypeIds::TALL_GRASS:
            case BlockTypeIds::CORNFLOWER:
            case BlockTypeIds::SUNFLOWER:
            case BlockTypeIds::POPPY:
            case BlockTypeIds::CHORUS_FLOWER:
                $this->addXp(self::LAWN_MOWER, $player);
                return;
        }
    }

    /**
     * @priority LOWEST
     */
    public function onPlace(BlockPlaceEvent $event) {
        if($event->isCancelled()) {
            return;
        }
        $player = $event->getPlayer();
        $block = $event->getBlockAgainst();
        if($block instanceof Opaque) {
            $this->addXp(self::BUILDER, $player);
        }
    }

    /**
     * @priority LOWEST
     */
    public function onDamage(EntityDamageEvent $event) {
        $entity = $event->getEntity();
        if($event->isCancelled()) {
            return;
        }
        if($event instanceof EntityDamageByEntityEvent) {
            if(!$entity instanceof Player) return;
            if(($damager = $event->getDamager()) instanceof Player) {
                if (($entity->getHealth() - $event->getFinalDamage()) <= 0) {
                    $this->addXp(self::KILLER, $damager);
                }
                $this->addXp(self::COMBAT, $damager);
            }
        }
    }

    /**
     * @priority LOWEST
     */
    public function onShootBow(EntityShootBowEvent $event) {
        if($event->isCancelled()) {
            return;
        }
        $entity = $event->getEntity();
        if($entity instanceof Player) {
            $this->addXp(self::ARCHER, $entity);
        }
    }

    /**
     * @priority LOWEST
     */
    public function onItemConsume(PlayerItemConsumeEvent $event) {
        if($event->getPlayer()->getHungerManager()->getFood() < $event->getPlayer()->getHungerManager()->getMaxFood()) {
            $this->addXp(self::CONSUMER, $event->getPlayer());
        }
    }
}
