<?php

namespace Drupal\wmmeta\EventSubscriber;

use Drupal\hook_event_dispatcher\Event\Entity\BaseEntityEvent;
use Drupal\hook_event_dispatcher\HookEventDispatcherEvents;
use Drupal\wmcustom\Entity\Eck\Meta\Meta;
use Drupal\wmcustom\Entity\Node\NodeModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SchedulerSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            HookEventDispatcherEvents::ENTITY_PRE_SAVE => 'setEntityStatus',
        ];
    }

    public function setEntityStatus(BaseEntityEvent $event)
    {
        /* @var \Drupal\Core\Entity\ContentEntityInterface $entity */
        $entity = $event->getEntity();

        if (!$entity instanceof NodeModel || !$entity->hasField('field_meta')) {
            return;
        }

        switch ($entity->getMeta()->getPublishedStatus()) {
            case Meta::PUBLISHED:
                return $this->publishEntity($entity);
            case Meta::DRAFT:
                return $this->unPublishEntity($entity);
            case Meta::SCHEDULED:
                return $this->scheduleEntity($entity);
        }
    }

    private function publishEntity(NodeModel $entity)
    {
        /** @var NodeModel $original */
        $original = $entity->original;
        $entity->setPublished();
        if (!$original || !$original->isPublished()) {
            $entity->setCreatedTime(time());
        }
        $this->clearScheduled($entity);
    }

    private function unPublishEntity(NodeModel $entity)
    {
        $entity->setUnpublished();
        $this->clearScheduled($entity);
    }

    private function scheduleEntity(NodeModel $entity)
    {
        $now = time();

        $publishOn = $entity->getMeta()->getPublishOn();
        if (!$publishOn) {
            $publishOn = $entity->getCreated();
        }

        $unpublishOn = $entity->getMeta()->getUnpublishOn();

        if ($publishOn && $stamp = $publishOn->getTimestamp()) {
            $entity->getMeta()->setPublishOn($publishOn);

            $entity->setUnpublished();

            if ($now >= $stamp) {
                $entity->setCreatedTime($stamp);
                $entity->setPublished();
            }
        }

        if ($unpublishOn && $stamp = $unpublishOn->getTimestamp()) {
            if ($now >= $stamp) {
                $entity->setUnpublished();
            }
        }
    }

    private function clearScheduled(NodeModel $entity)
    {
        $meta = $entity->getMeta();
        $meta->setPublishOn();
        $meta->setUnpublishOn();
        $meta->save();
    }
}
