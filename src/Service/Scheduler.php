<?php

namespace Drupal\wmmeta\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\node\NodeInterface;
use Drupal\wmmeta\Entity\Eck\Meta\Meta;

class Scheduler
{
    /** @var EntityStorageInterface */
    protected $storage;
    /** @var LanguageManagerInterface */
    protected $languageManager;
    /** @var \Psr\Log\LoggerInterface */
    protected $logger;
    /** @var \Drupal\Core\Database\Connection */
    protected $db;

    public function __construct(
        EntityTypeManagerInterface $etm,
        LanguageManagerInterface $languageManager,
        LoggerChannelFactoryInterface $loggerChannelFactory,
        Connection $db
    ) {
        $this->storage = $etm->getStorage('node');
        $this->logger = $loggerChannelFactory->get('wmmeta.scheduler');
        $this->languageManager = $languageManager;
        $this->db = $db;
    }

    public function runSchedule()
    {
        foreach ($this->languageManager->getLanguages() as $language) {
            $this->logger->debug(
                'Running scheduler for lang: ' . $language->getId()
            );
            $this->publishNodes($language->getId());
            $this->unPublishNodes($language->getId());
            $this->logger->debug(
                'Finished scheduler for lang: ' . $language->getId()
            );
        }
    }

    protected function publishNodes($langId)
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

    protected function unPublishNodes($langId)
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

    protected function shouldBePublished($langId)
    {
        $now = $this->getCurrentDate();

        $q = $this->db->select('node_field_data', 'n')->fields('n', ['nid']);

        $q->innerJoin(
            'node__field_meta',
            'nfm',
            'nfm.entity_id = n.nid'
        );
        $q->innerJoin(
            'meta__field_publish_status',
            'fps',
            "fps.entity_id = nfm.field_meta_target_id AND fps.langcode = '$langId'"
        );
        $q->innerJoin(
            'meta__field_publish_on',
            'fpo',
            "fpo.entity_id = nfm.field_meta_target_id AND fpo.langcode = '$langId'"
        );
        $q->leftJoin(
            'meta__field_unpublish_on',
            'fuo',
            "fuo.entity_id = nfm.field_meta_target_id AND fuo.langcode = '$langId'"
        );

        $q->condition('n.status', NodeInterface::NOT_PUBLISHED)
            ->condition('n.langcode', $langId)
            ->condition(
                'fps.field_publish_status_value',
                Meta::SCHEDULED
            )
            ->condition(
                'fpo.field_publish_on_value',
                $now,
                '<='
            )
            ->condition(
                $q->orConditionGroup()
                    ->condition(
                        'fuo.field_unpublish_on_value', null, 'IS NULL'
                    )
                    ->condition(
                        'fuo.field_unpublish_on_value',
                        $now,
                        '>'
                    )
            );

        $ids = $q->execute()->fetchCol();

        return $this->loadMultiple($ids, $langId);
    }

    protected function shouldBeUnpublished($langId)
    {
        $now = $this->getCurrentDate();

        $q = $this->db->select('node_field_data', 'n')->fields('n', ['nid']);

        $q->innerJoin(
            'node__field_meta',
            'nfm',
            'nfm.entity_id = n.nid'
        );
        $q->innerJoin(
            'meta__field_publish_status',
            'fps',
            "fps.entity_id = nfm.field_meta_target_id AND fps.langcode = '$langId'"
        );
        $q->innerJoin(
            'meta__field_publish_on',
            'fpo',
            "fpo.entity_id = nfm.field_meta_target_id AND fpo.langcode = '$langId'"
        );
        $q->leftJoin(
            'meta__field_unpublish_on',
            'fuo',
            "fuo.entity_id = nfm.field_meta_target_id AND fuo.langcode = '$langId'"
        );

        $q->condition('n.status', NodeInterface::PUBLISHED)
            ->condition('n.langcode', $langId)
            ->condition(
                'fps.field_publish_status_value',
                Meta::SCHEDULED
            )
            ->condition(
                'fuo.field_unpublish_on_value',
                $now,
                '<='
            );

        $ids = $q->execute()->fetchCol();

        return $this->loadMultiple($ids, $langId);
    }

    /**
     * @return NodeInterface[]
     */
    protected function loadMultiple($ids, $langId)
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

    protected function getCurrentDate()
    {
        $now = new DrupalDateTime('now', DateTimeItemInterface::STORAGE_TIMEZONE);

        return $now->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT);
    }
}
