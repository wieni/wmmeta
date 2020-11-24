<?php

namespace Drupal\wmmeta\Entity\Meta;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\eck\Entity\EckEntity;
use Drupal\imgix\Plugin\Field\FieldType\ImgixFieldType;
use Drupal\wmmodel\Entity\Interfaces\WmModelInterface;
use Drupal\wmmodel\Entity\Traits\WmModel;

/**
 * @Model(
 *     entity_type = "meta",
 *     bundle = "meta"
 * )
 */
class Meta extends EckEntity implements WmModelInterface
{
    use WmModel;

    public const DRAFT = 'draft';
    public const PUBLISHED = 'published';
    public const SCHEDULED = 'scheduled';

    public static function getStatuses(): array
    {
        return [
            self::DRAFT => 'Unpublished',
            self::PUBLISHED => 'Published',
            self::SCHEDULED => 'Scheduled',
        ];
    }

    public function getImage(): ?ImgixFieldType
    {
        if ($this->hasField('field_meta_description')) {
            return $this->get('field_meta_image')->first();
        }

        return null;
    }

    public function getDescription(): string
    {
        if ($this->hasField('field_meta_description')) {
            return (string) $this->get('field_meta_description')->value;
        }

        return '';
    }

    public function setDescription($description): void
    {
        if ($this->hasField('field_meta_description')) {
            $this->set('field_meta_description', $description);
        }
    }

    public function getPublishedStatus(): string
    {
        return (string) $this->get('field_publish_status')->value;
    }

    public function setPublishedStatus($status): void
    {
        $this->set('field_publish_status', $status);
    }

    public function getPublishOn(): ?\DateTimeInterface
    {
        return $this->getDateTime('field_publish_on');
    }

    public function setPublishOn(\DateTimeInterface $dateTime = null): void
    {
        if ($dateTime === null) {
            $this->set('field_publish_on', null);
            return;
        }

        $dateTime->setTimezone(new \DateTimeZone(DateTimeItemInterface::STORAGE_TIMEZONE));
        $this->setDateTime('field_publish_on', $dateTime);
    }

    public function getUnpublishOn(): ?\DateTimeInterface
    {
        return $this->getDateTime('field_unpublish_on');
    }

    public function setUnpublishOn(\DateTimeInterface $dateTime = null): void
    {
        if ($dateTime === null) {
            $this->set('field_unpublish_on', null);
            return;
        }

        $this->setDateTime('field_unpublish_on', $dateTime);
    }

    protected function getDateTime($fieldName): ?\DateTimeInterface
    {
        /** @var \DateTimeInterface $date */
        if (!$this->hasField($fieldName)) {
            return null;
        }

        /* @var \Drupal\Core\Field\FieldItemListInterface $fieldList */
        $fieldList = $this->get($fieldName);
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

        return \DateTime::createFromFormat('U', $date) ?: null;
    }

    protected function setDateTime($fieldName, \DateTimeInterface $dateTime): void
    {
        $this->set($fieldName, $dateTime->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT));
    }
}
