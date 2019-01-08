<?php

namespace Drupal\wmmeta\EventSubscriber;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\wmmedia\Event\MediaUsagesAlterEvent;
use Drupal\wmmedia\WmmediaEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MediaUsagesAlterSubscriber implements EventSubscriberInterface
{
    /** @var EntityTypeManagerInterface */
    protected $entityTypeManager;
    /** @var EntityFieldManagerInterface */
    protected $entityFieldManager;

    public function __construct(
        EntityTypeManagerInterface $entityTypeManager,
        EntityFieldManagerInterface $entityFieldManager
    ) {
        $this->entityTypeManager = $entityTypeManager;
        $this->entityFieldManager = $entityFieldManager;
    }

    public static function getSubscribedEvents()
    {
        $events[WmmediaEvents::MEDIA_USAGES_ALTER][] = 'onMediaUsagesAlter';

        return $events;
    }

    public function onMediaUsagesAlter(MediaUsagesAlterEvent $event)
    {
        $usages = &$event->getUsages();

        $storages = $this->getStorages();

        foreach ($usages as $entityTypeId => $bundles) {
            foreach ($bundles as $bundle => $fields) {
                /** @var FieldableEntityInterface $entity */
                foreach ($fields as $fieldName => $entity) {
                    if (
                        $entity->getEntityTypeId() !== 'meta'
                        || $entity->bundle() !== 'meta'
                    ) {
                        continue;
                    }

                    if ($host = $this->findHost($storages, $entity)) {
                        unset($usages[$entityTypeId][$bundle][$fieldName]);
                        $usages[$entityTypeId][$bundle]['field_meta'] = $host;
                    }
                }
            }
        }
    }

    /** @return EntityStorageInterface[] */
    protected function getStorages(): array
    {
        $storages = [];

        foreach ($this->entityTypeManager->getDefinitions() as $entityType) {
            if (!$entityType->entityClassImplements(FieldableEntityInterface::class)) {
                continue;
            }

            $fieldDefinitions = $this->entityFieldManager->getFieldStorageDefinitions($entityType->id());

            if (!isset($fieldDefinitions['field_meta'])) {
                continue;
            }

            $storages[] = $this->entityTypeManager->getStorage($entityType->id());
        }

        return $storages;
    }

    /** @return FieldableEntityInterface|null */
    protected function findHost(array $storages, FieldableEntityInterface $entity)
    {
        foreach ($storages as $storage) {
            $hosts = $storage->loadByProperties([
                'field_meta' => $entity->id(),
            ]);

            if (empty($hosts)) {
                continue;
            }

            return reset($hosts);
        }

        return null;
    }
}
