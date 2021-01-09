<?php

declare(strict_types=1);

namespace TeraTeam\boat;

use pocketmine\entity\Entity;
use pocketmine\inventory\ShapelessRecipe;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\plugin\PluginBase;

class Main extends PluginBase{

    public function onEnable() : void{
        //Register boat items
        ItemFactory::registerItem(new BoatItem(), true);
        $this->getServer()->getCraftingManager()->registerRecipe(
            new ShapelessRecipe(
                [
                    Item::get(Item::WOODEN_PLANKS, 0, 5),
                    Item::get(Item::WOODEN_SHOVEL, 0, 1)
                ],
                [Item::get(Item::BOAT, 0, 1)])
        );

        //Register boat entities
        Entity::registerEntity(BoatEntity::class, true);

        //Register event listeners
        $this->getServer()->getPluginManager()->registerEvents(new EventListener(), $this);
    }
}
