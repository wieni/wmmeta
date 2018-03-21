<?php

namespace Drupal\wmmeta\EventSubscriber;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\hook_event_dispatcher\Event\Entity\BaseEntityEvent;
use Drupal\hook_event_dispatcher\HookEventDispatcherEvents;
use Drupal\wmmeta\Entity\Eck\Meta\Meta;
use Drupal\wmmeta\Entity\EntityPublishedInterface;
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
        $entity = $event->getEntity();

        if (!$entity instanceof EntityPublishedInterface || !$entity->hasField('field_meta')) {
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

    private function publishEntity(EntityPublishedInterface $entity)
    {
        /** @var EntityPublishedInterface $original */
        $original = $entity->original;
        $entity->setPublished();
        if (!$original || !$original->isPublished()) {
            $entity->set('created', time());
        }
        $this->clearScheduled($entity);
    }

    private function unPublishEntity(EntityPublishedInterface $entity)
    {
        $entity->setUnpublished();
        $this->clearScheduled($entity);
    }

    private function scheduleEntity(EntityPublishedInterface $entity)
    {
        $now = time();

        $publishOn = $entity->getMeta()->getPublishOn();
        if (!$publishOn) {
            $publishOn = $this->getCreated($entity);
        }

        $unpublishOn = $entity->getMeta()->getUnpublishOn();

        if ($publishOn && $stamp = $publishOn->getTimestamp()) {
            $entity->getMeta()->setPublishOn($publishOn);

            $entity->setUnpublished();

            if ($now >= $stamp) {
                $entity->set('created', $stamp);
                $entity->setPublished();
            }
        }

        if ($unpublishOn && $stamp = $unpublishOn->getTimestamp()) {
            if ($now >= $stamp) {
                $entity->setUnpublished();
            }
        }
    }

    private function clearScheduled(EntityPublishedInterface $entity)
    {
        $meta = $entity->getMeta();
        $meta->setPublishOn();
        $meta->setUnpublishOn();
        $meta->save();
    }

    protected function getCreated(EntityPublishedInterface $entity)
    {
        /** @var \DateTime $date */
        if (!$entity->hasField('created')) {
            return null;
        }

        /* @var \Drupal\Core\Field\FieldItemListInterface $fieldList */
        $fieldList = $entity->get('created');
        /* @var \Drupal\Core\Field\FieldItemInterface $item */
        $item = $fieldList->first();
        $value = ($item) ? $item->getValue() : [];

        // Early check to see if the date is valid, pre validation dates are arrays.
        if (empty($value['value']) || is_array($value['value'])) {
            return null;
        }

        if (!(($date = $fieldList->date) || ($date = $fieldList->value))) {
            return null;
        }

        if ($date instanceof DrupalDateTime) {
            $date = $date->format('U');
        }

        return \DateTime::createFromFormat(
            'U',
            $date,
            (new \DateTimeZone(drupal_get_user_timezone()))
        );
    }
}
