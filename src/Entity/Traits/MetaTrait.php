<?php

namespace Drupal\wmmeta\Entity\Traits;

use Drupal\eck\Entity\EckEntity;

trait MetaTrait
{
    /**
     * @return EckEntity
     */
    public function getMeta()
    {
        /** @var EckEntity $meta */
        $meta = $this->get('field_meta')->entity;
        $langcode = $this->language()->getId();

        if (!$meta) {
            $meta = $this->entityTypeManager()
                ->getStorage('meta')
                ->create([
                    'type' => 'meta',
                ]);
            $meta = $meta->hasTranslation($langcode) ? $meta->getTranslation($langcode) : $meta->addTranslation($langcode);
            $this->field_meta = $meta;
            return $this->field_meta->entity;
        }

        $meta = $meta->hasTranslation($langcode) ? $meta->getTranslation($langcode) : $meta->addTranslation($langcode);

        return $meta;
    }

    public function toMetaOGArray(): array
    {
        $meta = [
            'title' => $this->label(),
            'description' => $this->getMeta()->getDescription(),
        ];

        if ($image = $this->getMeta()->getImage()) {
            $meta['image'] = $image;
        }

        return $meta;
    }
}
