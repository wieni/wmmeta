<?php

namespace Drupal\wmmeta\Entity\Meta;

use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\eck\Entity\EckEntity;
use Drupal\imgix\Plugin\Field\FieldType\ImgixFieldType;
use Drupal\wmmodel\Entity\Interfaces\WmModelInterface;
use Drupal\wmmodel\Entity\Traits\FieldHelpers;
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
    use FieldHelpers;

    public const DRAFT = 'draft';
    public const PUBLISHED = 'published';
    public const SCHEDULED = 'scheduled';

    public static function getStatuses(): array
    {
        return [
            self::DRAFT => t('Unpublished'),
            self::PUBLISHED => t('Published'),
            self::SCHEDULED => t('Scheduled'),
        ];
    }

    public function getImage()
    {
        if ($this->hasField('field_meta_image')) {
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
}
