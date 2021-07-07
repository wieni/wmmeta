<?php

namespace Drupal\wmmeta\EventSubscriber;

use Drupal\Core\Entity\EntityInterface;
use Drupal\wmmeta\Entity\EntityPublishedInterface;
use Drupal\wmmeta\Entity\Meta\Meta;

class SchedulerSubscriber
{
    public function setEntityStatus(EntityInterface $entity): void
    {
        if (
            !$entity instanceof EntityPublishedInterface
            || !$entity->hasField('field_meta')
            || empty($entity->get('field_meta')->target_id)
        ) {
            return;
        }

        switch ($entity->getMeta()->getPublishedStatus()) {
            case Meta::PUBLISHED:
                $this->publishEntity($entity);
                break;
            case Meta::DRAFT:
                $this->unPublishEntity($entity);
                break;
            case Meta::SCHEDULED:
                $this->scheduleEntity($entity);
        }
    }

    protected function publishEntity(EntityPublishedInterface $entity): void
    {
        $original = $entity->original;
        $entity->setPublished();

        if (!$original instanceof EntityPublishedInterface || !$original->isPublished()) {
            $this->setCreated($entity, time());
        }

        $this->clearScheduled($entity);
    }

    protected function unPublishEntity(EntityPublishedInterface $entity): void
    {
        $entity->setUnpublished();
        $this->clearScheduled($entity);
    }

    protected function scheduleEntity(EntityPublishedInterface $entity): void
    {
        $now = time();
        $publishOn = $entity->getMeta()->getPublishOn() ?? $this->getCreated($entity);
        $unpublishOn = $entity->getMeta()->getUnpublishOn();

        if ($publishOn && $stamp = $publishOn->getTimestamp()) {
            $entity->getMeta()->setPublishOn($publishOn);
            $entity->setUnpublished();
            $this->setCreated($entity, $stamp);

            if ($now >= $stamp) {
                $entity->setPublished();
            }
        }

        if ($unpublishOn && ($stamp = $unpublishOn->getTimestamp()) && $now >= $stamp) {
            $entity->setUnpublished();
        }
    }

    protected function clearScheduled(EntityPublishedInterface $entity): void
    {
        $meta = $entity->getMeta();
        $meta->setPublishOn();
        $meta->setUnpublishOn();
        $meta->save();
    }

    protected function getCreated(EntityPublishedInterface $entity): ?\DateTimeInterface
    {
        if (!$entity->hasField('created')) {
            return null;
        }

        $timestamp = $entity->get('created')->value;

        return \DateTime::createFromFormat('U', $timestamp)
            ->setTimezone(new \DateTimeZone(date_default_timezone_get()));
    }

    protected function setCreated(EntityPublishedInterface $entity, int $timestamp): void
    {
        if (!$entity->hasField('created')) {
            return;
        }

        $entity->set('created', $timestamp);
    }
}
