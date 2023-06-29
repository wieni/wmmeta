<?php

namespace Drupal\wmmeta\Entity\Traits;

use Drupal\wmmeta\Entity\Meta\Meta;

trait MetaTrait
{
    public function getMeta(): Meta
    {
        /** @var Meta $meta */
        $meta = $this->get('field_meta')->entity;
        $langcode = $this->language()->getId();

        if (!$meta) {
            $meta = $this->entityTypeManager()
                ->getStorage('meta')
                ->create(['type' => 'meta']);

            if (isset($meta->getTranslationLanguages(true)[$langcode])) {
                $meta = $meta->hasTranslation($langcode)
                    ? $meta->getTranslation($langcode)
                    : $meta->addTranslation($langcode);
            }

            $this->set('field_meta', $meta);

            return $this->field_meta->entity;
        }

        if (isset($meta->getTranslationLanguages(true)[$langcode])) {
            $meta = $meta->hasTranslation($langcode)
                ? $meta->getTranslation($langcode)
                : $meta->addTranslation($langcode);
        }

        return $meta;
    }

    public function toMetaOGArray(): array
    {
        $meta = [
            'title' => $this->label(),
            'description' => $this->getMeta()->getDescription(),
            'url' => $this->toUrl()->setAbsolute(true)->toString()
        ];

        if ($image = $this->getMeta()->getImage()) {
            $meta['image'] = $image;
        }

        if ($this->getOgType()) {
            $meta['type'] = $this->getOgType();
        }

        return $meta;
    }

    public function getOgType(): ?string
    {
        return null;
    }
}
