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

use AkmalFairuz\McMMO\command\McmmoCommand;
use AkmalFairuz\McMMO\command\McmmoSetupCommand;
use AkmalFairuz\McMMO\entity\FloatingText;
use pocketmine\scheduler\ClosureTask;
use pocketmine\block\Opaque;
use pocketmine\entity\EntityFactory;
use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\Human;
use pocketmine\entity\Location;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityShootBowEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerItemConsumeEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\block\BlockLegacyIds;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\world\World;
use pocketmine\nbt\tag\ByteArrayTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\nbt\tag\NameTag;
use pocketmine\utils\TextFormat;

class Main extends PluginBase implements Listener {

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
    public $database;

    /** @var Main */
    public static Main|null $instance;

    public function onEnable() : void {
        $this->saveResource("database.yml");
        $this->getServer()->getCommandMap()->register("mcmmo", new McmmoCommand("mcmmo", $this));
        $this->getServer()->getCommandMap()->register("mcmmoadmin", new McmmoSetupCommand("mcmmoadmin", $this));
        $this->database = yaml_parse(file_get_contents($this->getDataFolder() . "database.yml"));
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        // Entity::registerEntity(FloatingText::class, true);
        EntityFactory::getInstance()->register(FloatingText::class, function (World $world, CompoundTag $nbt) : FloatingText {
            return new FloatingText(EntityDataHelper::parseLocation($nbt, $world), Human::parseSkinNBT($nbt), $nbt);
        }, ["FloatingText", "FloatingTextEntity"]);
        $this->getScheduler()->scheduleRepeatingTask(new ClosureTask(
            function () {
                // TODO: Repeat top entity leaderboard
            }
        ), 20);
        self::$instance = $this;
    }

    public static function getInstance() : Main {
        return self::$instance;
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

    public function spawnFloatingText(int $type, Player $player){
		$nbt = CompoundTag::create();
		$nbt->setTag("Name", new StringTag($player->getSkin()->getSkinId()));
		$nbt->setTag("Data", new ByteArrayTag($player->getSkin()->getSkinData()));
		$nbt->setTag("CapeData", new ByteArrayTag($player->getSkin()->getCapeData()));
		$nbt->setTag("GeometryName", new StringTag($player->getSkin()->getGeometryName()));
		$nbt->setTag("GeometryData", new ByteArrayTag($player->getSkin()->getGeometryData()));
		$entity = new FloatingText(Location::fromObject($player->getPosition(), $player->getPosition()->getWorld(), $player->getLocation()->getYaw(), $player->getLocation()->getPitch()), $player->getSkin(), $nbt);
		$txt = "";
        $array = [];
        $a = ["Lumberjack", "Farmer", "Excavation", "Miner", "Killer", "Combat", "Builder", "Consumer", "Archer", "Lawn Mower"];
        foreach($this->getAll($type) as $k => $o){
            $array[mb_strtolower($k)] = $o;
        }
        arsort($array);
        $array = array_slice($array, 0, 20);
        $top = 1;
        foreach($array as $k => $o){
			$txt .= TextFormat::RED. $top . ") ".TextFormat::GREEN.$k.TextFormat::RED." : ".TextFormat::BLUE."Lv. ".$o."\n";
            $top++;
        }
        $entity->setNameTag(TextFormat::BOLD.TextFormat::AQUA."MCMMO Leaderboard\n".TextFormat::RESET.TextFormat::YELLOW.$a[$type].TextFormat::RESET . "\n\n" . $txt);
		$entity->setNameTagAlwaysVisible(true);
		$entity->spawnToAll();
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
        switch($block->getId()) {
            case BlockLegacyIds::WHEAT_BLOCK:
            case BlockLegacyIds::BEETROOT_BLOCK:
            case BlockLegacyIds::PUMPKIN_STEM:
            case BlockLegacyIds::PUMPKIN:
            case BlockLegacyIds::MELON_STEM:
            case BlockLegacyIds::MELON_BLOCK:
            case BlockLegacyIds::CARROT_BLOCK:
            case BlockLegacyIds::POTATO_BLOCK:
            case BlockLegacyIds::SUGARCANE_BLOCK:
                $this->addXp(self::FARMER, $player);
                return;
            case BlockLegacyIds::STONE:
            case BlockLegacyIds::DIAMOND_ORE:
            case BlockLegacyIds::GOLD_ORE:
            case BlockLegacyIds::REDSTONE_ORE:
            case BlockLegacyIds::IRON_ORE:
            case BlockLegacyIds::COAL_ORE:
            case BlockLegacyIds::EMERALD_ORE:
            case BlockLegacyIds::OBSIDIAN:
                $this->addXp(self::MINER, $player);
                return;
            case BlockLegacyIds::LOG:
            case BlockLegacyIds::LOG2:
            case BlockLegacyIds::LEAVES:
            case BlockLegacyIds::LEAVES2:
                $this->addXp(self::LUMBERJACK, $player);
                return;
            case BlockLegacyIds::DIRT:
            case BlockLegacyIds::GRASS:
            case BlockLegacyIds::GRASS_PATH:
            case BlockLegacyIds::FARMLAND:
            case BlockLegacyIds::SAND:
            case BlockLegacyIds::GRAVEL:
                $this->addXp(self::EXCAVATION, $player);
                return;
            case BlockLegacyIds::TALL_GRASS:
            case BlockLegacyIds::YELLOW_FLOWER:
            case BlockLegacyIds::RED_FLOWER:
            case BlockLegacyIds::CHORUS_FLOWER:
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
        $block = $event->getBlock();
        if($block instanceof Opaque) {
            $this->addXp(self::BUILDER, $player);
            return;
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
