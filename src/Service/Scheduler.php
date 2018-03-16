<?php

namespace Drupal\wmmeta\Service;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\node\NodeInterface;
use Drupal\wmcustom\Entity\Eck\Meta\Meta;

class Scheduler
{
    /** @var EntityStorageInterface */
    private $storage;
    /** @var LanguageManagerInterface */
    private $languageManager;
    /** @var \Drupal\Core\Logger\LoggerChannelInterface */
    private $logger;

    public function __construct(
        EntityTypeManagerInterface $etm,
        LanguageManagerInterface $languageManager,
        LoggerChannelFactoryInterface $loggerChannelFactory
    ) {
        $this->storage = $etm->getStorage('node');
        $this->logger = $loggerChannelFactory->get('wmmeta.scheduler');
        $this->languageManager = $languageManager;
    }

    public function runSchedule()
    {
        foreach ($this->languageManager->getLanguages() as $language) {
            $this->logger->info(
                'Running scheduler for lang: ' . $language->getId()
            );
            $this->publishNodes($language->getId());
            $this->unPublishNodes($language->getId());
            $this->logger->info(
                'Finished scheduler for lang: ' . $language->getId()
            );
        }
    }

    private function publishNodes($langId)
    {
        foreach ($this->shouldBePublished($langId) as $node) {
            $this->logger->info(sprintf(
                'Publishing scheduled %s:%s:%s',
                $node->bundle(),
                $node->language()->getId(),
                $node->id()
            ));
            $node->setPublished();
            $node->save();
        }
    }

    private function unPublishNodes($langId)
    {
        foreach ($this->shouldBeUnpublished($langId) as $node) {
            $this->logger->info(sprintf(
                'Unpublishing scheduled %s:%s:%s',
                $node->bundle(),
                $node->language()->getId(),
                $node->id()
            ));
            $node->setUnpublished();
            $node->save();
        }
    }

    private function shouldBePublished($langId)
    {
        $now = $this->getCurrentDate();

        $ids = $this->storage->getQuery()
            ->condition('status', NodeInterface::NOT_PUBLISHED, null, $langId)
            ->condition('langcode', $langId, null, $langId)
            ->condition(
                'field_meta.entity.field_publish_status',
                Meta::SCHEDULED,
                null,
                $langId
            )
            ->condition(
                'field_meta.entity.field_publish_on', $now, '<=', $langId
            )
            ->condition(
                $this->storage->getQuery()
                    ->orConditionGroup()
                    ->notExists(
                        'field_meta.entity.field_unpublish_on', $langId
                    )
                    ->condition(
                        'field_meta.entity.field_unpublish_on',
                        $now,
                        '>',
                        $langId
                    )
            )
            ->execute();

        return $this->loadMultiple($ids, $langId);
    }

    private function shouldBeUnpublished($langId)
    {
        $now = $this->getCurrentDate();

        $ids = $this->storage->getQuery()
            ->condition('status', NodeInterface::PUBLISHED, null, $langId)
            ->condition('langcode', $langId, null, $langId)
            ->condition(
                'field_meta.entity.field_publish_status',
                Meta::SCHEDULED,
                null,
                $langId
            )
            ->condition(
                'field_meta.entity.field_unpublish_on', $now, '<=', $langId
            )
            ->execute();

        return $this->loadMultiple($ids, $langId);
    }

    /**
     * @return NodeInterface[]
     */
    private function loadMultiple($ids, $langId)
    {
        if (!$ids) {
            return [];
        }

        $nodes = array_map(
            function (NodeInterface $node) use ($langId) {
                if (!$node->hasTranslation($langId)) {
                    return null;
                }

                return $node->getTranslation($langId);
            },
            $this->storage->loadMultiple($ids)
        );

        return array_filter($nodes);
    }

    private function getCurrentDate()
    {
        $now = new DrupalDateTime('now', DateTimeItemInterface::STORAGE_TIMEZONE);

        return $now->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT);
    }
}
