<?php

namespace Drupal\wmmeta\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\file\FileInterface;
use Drupal\image\Entity\ImageStyle;
use Drupal\wmmedia\Plugin\Field\FieldType\MediaFileExtras;
use Drupal\wmmedia\Plugin\Field\FieldType\MediaImageExtras;
use Drupal\wmmeta\Entity\EntityMetaInterface;

class MetaService
{
    /** @var LanguageManagerInterface */
    protected $languageManager;
    /** @var ConfigFactoryInterface */
    protected $configFactory;

    /** @var array */
    protected $defaultMeta;
    /** @var EntityMetaInterface|null */
    protected $entity;

    public function __construct(
        LanguageManagerInterface $languageManager,
        ConfigFactoryInterface $configFactory
    ) {
        $this->languageManager = $languageManager;
        $this->configFactory = $configFactory;
    }

    public function getMetaData(): array
    {
        $meta = $this->getDefaultMetaData();

        if ($entity = $this->getEntity()) {
            $meta = array_merge($meta, $this->getEntityMetaData($entity));
        }

        if (!is_string($meta['image'])) {
            $meta['image'] = $this->getImageUrl($meta['image']) ?? '';
        }

        return $meta;
    }

    public function getDefaultMetaData(): array
    {
        if (empty($this->defaultMeta)) {
            $this->defaultMeta = [
                'site_name' => $this->configFactory->get('system.site')->get('name'),
                'locale' => $this->getLocale(),
                'description' => '',
                'image' => '',
                'image_width' => '1280',
                'image_height' => '720',
            ];
        }

        if (empty($this->defaultMeta['twitter_handle'])) {
            $this->defaultMeta['twitter_handle'] = $this->configFactory->get('wmmeta.settings')->get('site.twitter_handle');
        }

        return $this->defaultMeta;
    }

    public function getEntity(): ?EntityMetaInterface
    {
        return $this->entity;
    }

    public function setEntity(?EntityMetaInterface $entity): void
    {
        $this->entity = $entity;
    }

    protected function getEntityMetaData(EntityMetaInterface $entity): array
    {
        return array_filter($entity->toMetaOGArray());
    }

    protected function getImageUrl($file = null): ?string
    {
        if (
            $file instanceof MediaImageExtras
            || $file instanceof MediaFileExtras
        ) {
            $file = $file->getFile();
        }

        if (!$file instanceof FileInterface) {
            return null;
        }

        $path = $file->getFileUri();

        if (!$imageStyle = ImageStyle::load('og')) {
            return null;
        }

        if (!$imageStyle->supportsUri($path)) {
            return null;
        }

        return $imageStyle->buildUrl($path);
    }

    protected function getLocale(): string
    {
        $locales = [
            'en' => 'en_GB',
            'nl' => 'nl_BE',
            'fr' => 'fr_BE',
        ];

        return $locales[$this->getCurrentLangcode()] ?? 'en_GB';
    }

    protected function getCurrentLangcode(): string
    {
        return $this->languageManager->getCurrentLanguage()->getId();
    }
}
