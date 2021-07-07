<?php

namespace Drupal\wmmeta\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\imgix\ImgixManagerInterface;
use Drupal\imgix\Plugin\Field\FieldType\ImgixFieldType;
use Drupal\wmmedia\Plugin\Field\FieldType\MediaFileExtras;
use Drupal\wmmedia\Plugin\Field\FieldType\MediaImageExtras;
use Drupal\wmmeta\Entity\EntityMetaInterface;

class MetaService
{
    /** @var ImgixManagerInterface */
    protected $imgix;
    /** @var LanguageManagerInterface */
    protected $languageManager;
    /** @var ConfigFactoryInterface */
    protected $configFactory;

    /** @var array */
    protected $defaultMeta;
    /** @var EntityMetaInterface|null */
    protected $entity;

    public function __construct(
        ImgixManagerInterface $imgix,
        LanguageManagerInterface $languageManager,
        ConfigFactoryInterface $configFactory
    ) {
        $this->configFactory = $configFactory;
        $this->imgix = $imgix;
        $this->languageManager = $languageManager;
    }

    public function getMetaData(): array
    {
        $meta = $this->getDefaultMetaData();

        if ($entity = $this->getEntity()) {
            $meta = array_merge($meta, $this->getEntityMetaData($entity));
        }

        if (!is_string($meta['image'])) {
            $meta['image'] = $this->getImgixUrl($meta['image']);
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

    protected function getImgixUrl($file = null): ?string
    {
        if (
            $file instanceof MediaImageExtras
            || $file instanceof MediaFileExtras
            || $file instanceof ImgixFieldType
        ) {
            $file = $file->getFile();
        }

        if (!$file) {
            return '';
        }

        return $this->imgix->getImgixUrlByPreset($file, 'og');
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
