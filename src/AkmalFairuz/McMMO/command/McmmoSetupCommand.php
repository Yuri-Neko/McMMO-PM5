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

namespace AkmalFairuz\McMMO\command;

use pocketmine\command\CommandSender;
use pocketmine\command\Command;

use pocketmine\player\Player;

use pocketmine\nbt\tag\ByteArrayTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\StringTag;

use pocketmine\entity\Location;

use AkmalFairuz\McMMO\entity\FloatingText;

class McmmoSetupCommand extends Command {

    public function __construct() {
        parent::__construct("mcmmoadmin", "Admin McMMO Command", null, []);
        $this->setPermission("mcmmo.admin");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool
    {
        if(!$sender instanceof Player) {
            $sender->sendMessage("Please use command in-game");
            return true;
        }

        if(!$sender->hasPermission("mcmmo.admin")) {
            $sender->sendMessage("You don't have permission to use this command");
            return true;
        }

        $a = ["lumberjack", "farmer", "excavation", "miner", "killer", "combat", "builder", "consumer", "archer", "lawnmower"];
        if(count($args) < 1) {
            $sender->sendMessage("Usage: /mcmmoadmin setup ".implode("/" , $a)."> (to spawn floating text) | /mcmmoadmin remove (to remove nearly floating text)");
            return true;
        }
        if($args[0] === "remove") {
            $maxDistance = 3;
            $g = 0;
            foreach($sender->getWorld()->getNearbyEntities($sender->getBoundingBox()->expandedCopy($maxDistance, $maxDistance, $maxDistance)) as $entity){
                if($entity instanceof FloatingText) {
                    $g++;
                    $entity->close();
                }
            }
            $sender->sendMessage("Removed ".$g." floating text");
            return true;
        }
        if($args[0] === "setup") {
            if(!isset($args[1])) {
                $sender->sendMessage("Usage: /mcmmoadmin setup ".implode("/" , $a)."> (to spawn floating text)");
                return true;
            }
            if(!in_array($args[1], $a)) {
                $sender->sendMessage("Usage: /mcmmoadmin setup ".implode("/" , $a)."> (to spawn floating text)");
                return true;
            }
            $a = ["lumberjack" => 0, "farmer" => 1, "excavation" => 2, "miner" => 3, "killer" => 4, "combat" => 5, "builder" => 6, "consumer" => 7, "archer" => 8, "lawnmower" => 9];
            $nbt = CompoundTag::create();
            $nbt->setTag("Name", new StringTag($sender->getSkin()->getSkinId()));
            $nbt->setTag("Data", new ByteArrayTag($sender->getSkin()->getSkinData()));
            $nbt->setTag("CapeData", new ByteArrayTag($sender->getSkin()->getCapeData()));
            $nbt->setTag("GeometryName", new StringTag($sender->getSkin()->getGeometryName()));
            $nbt->setTag("GeometryData", new ByteArrayTag($sender->getSkin()->getGeometryData()));
            $nbt->setInt("type", $a[$args[1]]);
            $entity = new FloatingText(Location::fromObject($sender->getPosition(), $sender->getPosition()->getWorld(), $sender->getLocation()->getYaw(), $sender->getLocation()->getPitch()), $sender->getSkin(), $nbt);
            $entity->setNameTagAlwaysVisible();
            $entity->spawnToAll();
        }
        return true;
    }
}
