<?php

namespace Drupal\wmmeta\Entity\Eck\Meta;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\eck\Entity\EckEntity;
use Drupal\imgix\Plugin\Field\FieldType\ImgixFieldType;
use Drupal\wmmodel\Entity\Interfaces\WmModelInterface;
use Drupal\wmmodel\Entity\Traits\WmModel;

class Meta extends EckEntity implements WmModelInterface, ContentEntityInterface
{
    use WmModel;

    const DRAFT = 'draft';
    const PUBLISHED = 'published';
    const SCHEDULED = 'scheduled';

    public static function getStatuses()
    {
        return [
            self::DRAFT => 'Unpublished',
            self::PUBLISHED => 'Published',
            self::SCHEDULED => 'Scheduled',
        ];
    }

    /**
     * @return ImgixFieldType
     */
    public function getImage()
    {
        return $this->get('field_meta_image')->first();
    }

    public function getDescription()
    {
        return (string) $this->get('field_meta_description')->value;
    }

    public function setDescription($description)
    {
        $this->set('field_meta_description', $description);
    }

    public function getPublishedStatus()
    {
        return (string) $this->get('field_publish_status')->value;
    }

    public function setPublishedStatus($status)
    {
        $this->set('field_publish_status', $status);
    }

    public function getPublishOn()
    {
        return $this->getDateTime('field_publish_on');
    }

    public function setPublishOn(\DateTime $dateTime = null)
    {
        if (empty($dateTime)) {
            $this->set('field_publish_on', null);
            return;
        }

        $dateTime->setTimezone(new \DateTimeZone(DateTimeItemInterface::STORAGE_TIMEZONE));
        $this->setDateTime('field_publish_on', $dateTime);
    }

    public function getUnpublishOn()
    {
        return $this->getDateTime('field_unpublish_on');
    }

    public function setUnpublishOn(\DateTime $dateTime = null)
    {
        if (empty($dateTime)) {
            $this->set('field_unpublish_on', null);
            return;
        }

        $this->setDateTime('field_unpublish_on', $dateTime);
    }

    protected static function bundleDeduceRegex()
    {
        return '#/Eck/(.*?)/(.*?)$#';
    }

    /**
     * @return bool|\DateTime|null
     */
    protected function getDateTime($fieldName)
    {
        /** @var \DateTime $date */
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

        return \DateTime::createFromFormat(
            'U',
            $date,
            (new \DateTimeZone(drupal_get_user_timezone()))
        );
    }

    protected function setDateTime($fieldName, \DateTime $dateTime)
    {
        $this->set($fieldName, $dateTime->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT));
    }
}
