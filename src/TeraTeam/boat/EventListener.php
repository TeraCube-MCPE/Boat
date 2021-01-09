<?php

namespace TeraTeam\boat;

use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDeathEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\{
    InteractPacket, InventoryTransactionPacket, MoveActorAbsolutePacket as MoveEntityAbsolutePacket, PlayerInputPacket, SetActorMotionPacket as SetEntityMotionPacket, AnimatePacket
};

class EventListener implements Listener{
    /**
     * @param PlayerQuitEvent $event
     */
    public function onPlayerQuitEvent(PlayerQuitEvent $event) : void{
        $player = $event->getPlayer();
        if($player->getDataFlag(Entity::DATA_FLAGS, Entity::DATA_FLAG_RIDING)){
            foreach($player->getLevel()->getNearbyEntities($player->getBoundingBox()->expand(2, 2, 2), $player) as $key => $entity){
                if($entity instanceof BoatEntity && $entity->unlink($player)){
                    return;
                }
            }
        }
    }

    /**
     * @param EntityDeathEvent $event
     * @priority MONITOR
     */
    public function onDeath(EntityDeathEvent $event): void
    {
        if($event->getEntity() instanceof BoatEntity){
            $event->setDrops([]);
        }
    }

    /**
     * @param DataPacketReceiveEvent $event
     */
    public function onDataPacketReceiveEvent(DataPacketReceiveEvent $event) : void{
        $packet = $event->getPacket();
        $player = $event->getPlayer();
        if($packet instanceof InventoryTransactionPacket && $packet->transactionType === InventoryTransactionPacket::TYPE_USE_ITEM_ON_ENTITY){
            $entity = $player->getLevel()->getEntity($packet->trData->entityRuntimeId);
            if($entity instanceof BoatEntity){
                if($packet->trData->actionType === InventoryTransactionPacket::USE_ITEM_ON_ENTITY_ACTION_INTERACT){
                    if($entity->canLink($player)){
                        $entity->link($player);
                    }
                    $event->setCancelled();
                }
            }
        }elseif($packet instanceof InteractPacket){
            $entity = $player->getLevel()->getEntity($packet->target);
            if($entity instanceof BoatEntity){
                if($packet->action === InteractPacket::ACTION_LEAVE_VEHICLE && $entity->isRider($player)){
                    $entity->unlink($player);
                }
                $event->setCancelled();
            }
        }elseif($packet instanceof MoveEntityAbsolutePacket){
            $entity = $player->getLevel()->getEntity($packet->entityRuntimeId);
            if($entity instanceof BoatEntity && $entity->isRider($player)){
                $entity->absoluteMove($packet->position, $packet->xRot, $packet->zRot);
                $event->setCancelled();
            }
        }elseif($packet instanceof AnimatePacket){
            foreach($player->getLevel()->getEntities() as $entity){
                if($entity instanceof BoatEntity && $entity->isRider($player)){
                    switch($packet->action){
                        case BoatEntity::ACTION_ROW_RIGHT:
                        case BoatEntity::ACTION_ROW_LEFT:
                            $entity->handleAnimatePacket($packet);
                            $event->setCancelled();
                            break;
                    }
                    break;
                }
            }
        }elseif($packet instanceof PlayerInputPacket || $packet instanceof SetEntityMotionPacket){
            if($player->getDataFlag(Entity::DATA_FLAGS, Entity::DATA_FLAG_RIDING)){
                //TODO: Handle PlayerInputPacket and SetEntityMotionPacket
                $event->setCancelled();
            }
        }
    }
}