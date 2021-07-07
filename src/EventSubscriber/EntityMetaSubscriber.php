<?php

namespace Drupal\wmmeta\EventSubscriber;

use Drupal\wmcontroller\Event\MainEntityEvent;
use Drupal\wmcontroller\WmcontrollerEvents;
use Drupal\wmmeta\Entity\EntityMetaInterface;
use Drupal\wmmeta\Service\MetaService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class EntityMetaSubscriber implements EventSubscriberInterface
{
    /** @var MetaService */
    protected $metaService;

    public function __construct(
        MetaService $metaService
    ) {
        $this->metaService = $metaService;
    }

    public static function getSubscribedEvents(): array
    {
        $events[WmcontrollerEvents::MAIN_ENTITY_RENDER][] = 'onMainEntity';
        return $events;
    }

    public function onMainEntity(MainEntityEvent $event): void
    {
        $entity = $event->getEntity();

        if (!$entity instanceof EntityMetaInterface || !$entity->hasField('field_meta')) {
            return;
        }

        $this->metaService->setEntity($entity);
    }
}
