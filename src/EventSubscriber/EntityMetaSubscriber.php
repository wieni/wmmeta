<?php

namespace Drupal\wmmeta\EventSubscriber;

use Drupal\wmcontroller\Event\MainEntityEvent;
use Drupal\wmcontroller\WmcontrollerEvents;
use Drupal\wmmeta\Entity\MetaDataInterface;
use Drupal\wmmeta\Service\MetaService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class EntityMetaSubscriber implements EventSubscriberInterface
{
    /** @var MetaService */
    private $metaService;

    public function __construct(
        MetaService $metaService
    ) {
        $this->metaService = $metaService;
    }

    public static function getSubscribedEvents()
    {
        $events[WmcontrollerEvents::MAIN_ENTITY_RENDER][] = 'onMainEntity';
        return $events;
    }

    public function onMainEntity(MainEntityEvent $event)
    {
        $entity = $event->getEntity();

        if (!$entity instanceof MetaDataInterface || !$entity->hasField('field_meta')) {
            return;
        }

        $this->metaService->setEntity($entity);
    }
}
