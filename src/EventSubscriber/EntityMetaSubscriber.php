<?php

namespace Drupal\wmmeta\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\imgix\ImgixManager;
use Drupal\imgix\Plugin\Field\FieldType\ImgixFieldType;
use Drupal\wmcontroller\Event\MainEntityEvent;
use Drupal\wmcontroller\WmcontrollerEvents;
use Drupal\wmmeta\Entity\MetaDataInterface;
use Drupal\wmmedia\Plugin\Field\FieldType\MediaFileExtras;
use Drupal\wmmedia\Plugin\Field\FieldType\MediaImageExtras;
use Drupal\wmsingles\Service\WmSingles;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class EntityMetaSubscriber implements EventSubscriberInterface
{
    /** @var \Drupal\wmcustom\Entity\Node\Homepage */
    private $homepage;
    /** @var ImgixManager */
    private $imgix;
    /** @var array */
    private $meta;
    /** @var \Drupal\Core\Language\LanguageManagerInterface */
    private $languageManager;
    /** @var \Drupal\Core\Config\ImmutableConfig */
    private $config;

    public function __construct(
        WmSingles $singles,
        ImgixManager $imgix,
        LanguageManagerInterface $languageManager,
        ConfigFactoryInterface $configFactory
    ) {
        $this->config = $configFactory->get('system.site');
        $this->homepage = $singles->getSingleByBundle('homepage');
        $this->imgix = $imgix;
        $this->languageManager = $languageManager;
    }

    public static function getSubscribedEvents()
    {
        $events[WmcontrollerEvents::MAIN_ENTITY_RENDER][] = 'onMainEntity';
        return $events;
    }

    public function onMainEntity(MainEntityEvent $event)
    {
        $entity = $event->getEntity();
        if (!$this->homepage || !$entity instanceof MetaDataInterface) {
            return;
        }

        $meta = array_merge(
            $this->defaultMeta(),
            $this->getMetaData($entity)
        );

        $this->meta = $meta;
    }

    public function getMeta()
    {
        $meta = $this->meta ?? $this->defaultMeta();

        if (!is_string($meta['image'])) {
            $meta['image'] = $this->imgixUrl($meta['image']);
        }

        return $meta;
    }

    private function defaultMeta()
    {
        $default = [
            'site_name' => $this->config->get('name'),
            'locale' => $this->getLocale(),
            'description' => '',
            'image' => '',
            'image_width' => '1280',
            'image_height' => '720',
        ];

        if (!$this->homepage) {
            return $default;
        }

        return array_merge(
            $default,
            $this->getMetaData($this->homepage)
        );
    }

    private function getMetaData(MetaDataInterface $metaData)
    {
        return array_filter($metaData->toMetaOGArray());
    }

    private function imgixUrl($file = null)
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

    private function getLocale()
    {
        $locales = [
            'en' => 'en_GB',
            'nl' => 'nl_BE',
            'fr' => 'fr_BE',
        ];
        return $locales[$this->getCurrentLangcode()] ?? 'en_GB';
    }

    private function getCurrentLangcode()
    {
        return $this->languageManager->getCurrentLanguage()->getId();
    }
}
