<?php

namespace Drupal\wmmeta\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\imgix\ImgixManager;
use Drupal\imgix\Plugin\Field\FieldType\ImgixFieldType;
use Drupal\wmmedia\Plugin\Field\FieldType\MediaFileExtras;
use Drupal\wmmedia\Plugin\Field\FieldType\MediaImageExtras;
use Drupal\wmmeta\Entity\MetaDataInterface;

class MetaService
{
    /** @var ImgixManager */
    protected $imgix;
    /** @var LanguageManagerInterface */
    protected $languageManager;
    /** @var ConfigFactoryInterface */
    protected $configFactory;

    /** @var array */
    protected $defaultMeta;
    /** @var MetaDataInterface */
    protected $entity;

    public function __construct(
        ImgixManager $imgix,
        LanguageManagerInterface $languageManager,
        ConfigFactoryInterface $configFactory
    ) {
        $this->configFactory = $configFactory;
        $this->imgix = $imgix;
        $this->languageManager = $languageManager;
    }

    public function getMetaData()
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

    protected function getDefaultMetaData()
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

        return $this->defaultMeta;
    }

    /**
     * @return MetaDataInterface|null
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * @param MetaDataInterface $entity
     */
    public function setEntity(MetaDataInterface $entity)
    {
        $this->entity = $entity;
    }

    protected function getEntityMetaData(MetaDataInterface $entity)
    {
        return array_filter($entity->toMetaOGArray());
    }

    protected function getImgixUrl($file = null)
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

    protected function getLocale()
    {
        $locales = [
            'en' => 'en_GB',
            'nl' => 'nl_BE',
            'fr' => 'fr_BE',
        ];
        return $locales[$this->getCurrentLangcode()] ?? 'en_GB';
    }

    protected function getCurrentLangcode()
    {
        return $this->languageManager->getCurrentLanguage()->getId();
    }
}
