<?php

namespace Drupal\wmmeta\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\wmmeta\Entity\Meta\Meta;
use Psr\Log\LoggerInterface;

class Scheduler
{
    /** @var EntityTypeManagerInterface */
    protected $entityTypeManager;
    /** @var EntityFieldManagerInterface */
    protected $entityFieldManager;
    /** @var LanguageManagerInterface */
    protected $languageManager;
    /** @var LoggerInterface */
    protected $logger;
    /** @var Connection */
    protected $db;

    public function __construct(
        EntityTypeManagerInterface $entityTypeManager,
        EntityFieldManagerInterface $entityFieldManager,
        LanguageManagerInterface $languageManager,
        LoggerChannelFactoryInterface $loggerChannelFactory,
        Connection $db
    ) {
        $this->entityTypeManager = $entityTypeManager;
        $this->entityFieldManager = $entityFieldManager;
        $this->logger = $loggerChannelFactory->get('wmmeta.scheduler');
        $this->languageManager = $languageManager;
        $this->db = $db;
    }

    public function runSchedule(): void
    {
        foreach ($this->getEntityTypes() as $entityType) {
            foreach ($this->languageManager->getLanguages() as $language) {
                foreach ($this->shouldBePublished($entityType, $language->getId()) as $entity) {
                    $this->doPublish($entity);
                }

                foreach ($this->shouldBeUnpublished($entityType, $language->getId()) as $entity) {
                    $this->doUnPublish($entity);
                }
            }

            $this->logger->debug(sprintf(
                'Finished %s scheduler',
                $entityType->getLabel()
            ));
        }
    }

    protected function doPublish(ContentEntityInterface $entity): void
    {
        $entityType = $entity->getEntityType();
        $bundle = $entity->get($entityType->getKey('bundle'))->entity;

        $this->logger->info(sprintf(
            'Publishing scheduled %s with bundle %s, language %s and id %s',
            $entityType->getLabel(),
            $bundle->label(),
            $entity->language()->getId(),
            $entity->id()
        ));

        $entity->setPublished();
        $entity->save();
    }

    protected function doUnPublish(ContentEntityInterface $entity): void
    {
        $entityType = $entity->getEntityType();
        $bundle = $entity->get($entityType->getKey('bundle'))->entity;

        $this->logger->info(sprintf(
            'Unpublishing scheduled %s with bundle %s, language %s and id %s',
            $entityType->getLabel(),
            $bundle->label(),
            $entity->language()->getId(),
            $entity->id()
        ));

        $entity->setUnpublished();
        $entity->save();
    }

    /** @return ContentEntityInterface[] */
    protected function shouldBePublished(EntityTypeInterface $entityType, string $langcode): array
    {
        $now = $this->getCurrentDate();
        $storage = $this->entityTypeManager->getStorage($entityType->id());

        $q = $this->db
            ->select($entityType->getDataTable(), 'data')
            ->fields('data', [$entityType->getKey('id')]);

        $q->innerJoin(
            sprintf('%s__field_meta', $entityType->id()),
            'fm',
            sprintf('fm.entity_id = data.%s', $entityType->getKey('id'))
        );
        $q->innerJoin(
            'meta__field_publish_status',
            'fps',
            sprintf('fps.entity_id = fm.field_meta_target_id AND fps.langcode = \'%s\'', $langcode)
        );
        $q->innerJoin(
            'meta__field_publish_on',
            'fpo',
            sprintf('fpo.entity_id = fm.field_meta_target_id AND fpo.langcode = \'%s\'', $langcode)
        );
        $q->leftJoin(
            'meta__field_unpublish_on',
            'fuo',
            sprintf('fuo.entity_id = fm.field_meta_target_id AND fuo.langcode = \'%s\'', $langcode)
        );

        $q->condition(sprintf('data.%s', $entityType->getKey('published')), 0)
            ->condition('data.langcode', $langcode)
            ->condition('fps.field_publish_status_value', Meta::SCHEDULED)
            ->condition('fpo.field_publish_on_value', $now, '<=')
            ->condition(
                $q->orConditionGroup()
                    ->condition('fuo.field_unpublish_on_value', null, 'IS NULL')
                    ->condition('fuo.field_unpublish_on_value', $now, '>')
            );

        $ids = $q->execute()->fetchCol();

        return $this->loadMultiple($storage, $ids, $langcode);
    }

    /** @return ContentEntityInterface[] */
    protected function shouldBeUnpublished(EntityTypeInterface $entityType, string $langcode): array
    {
        $now = $this->getCurrentDate();
        $storage = $this->entityTypeManager->getStorage($entityType->id());

        $q = $this->db
            ->select($entityType->getDataTable(), 'data')
            ->fields('data', [$entityType->getKey('id')]);

        $q->innerJoin(
            sprintf('%s__field_meta', $entityType->id()),
            'fm',
            sprintf('fm.entity_id = data.%s', $entityType->getKey('id'))
        );
        $q->innerJoin(
            'meta__field_publish_status',
            'fps',
            sprintf('fps.entity_id = fm.field_meta_target_id AND fps.langcode = \'%s\'', $langcode)
        );
        $q->innerJoin(
            'meta__field_publish_on',
            'fpo',
            sprintf('fpo.entity_id = fm.field_meta_target_id AND fpo.langcode = \'%s\'', $langcode)
        );
        $q->leftJoin(
            'meta__field_unpublish_on',
            'fuo',
            sprintf('fuo.entity_id = fm.field_meta_target_id AND fuo.langcode = \'%s\'', $langcode)
        );

        $q->condition(sprintf('data.%s', $entityType->getKey('published')), 1)
            ->condition('data.langcode', $langcode)
            ->condition('fps.field_publish_status_value', Meta::SCHEDULED)
            ->condition('fuo.field_unpublish_on_value', $now, '<=');

        $ids = $q->execute()->fetchCol();

        return $this->loadMultiple($storage, $ids, $langcode);
    }

    /** @return ContentEntityInterface[] */
    protected function loadMultiple(EntityStorageInterface $storage, array $ids, string $langId): array
    {
        if (empty($ids)) {
            return [];
        }

        $entities = array_map(
            function (ContentEntityInterface $entity) use ($langId): ?ContentEntityInterface {
                if (!$entity->hasTranslation($langId)) {
                    return null;
                }

                return $entity->getTranslation($langId);
            },
            $storage->loadMultiple($ids)
        );

        return array_filter($entities);
    }

    /** Get the current date in a storage-suitable format */
    protected function getCurrentDate(): string
    {
        return (new DrupalDateTime('now', DateTimeItemInterface::STORAGE_TIMEZONE))
            ->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT);
    }

    /**
     * Get all publishable entity types with field_meta
     * @return EntityTypeInterface[]
     */
    protected function getEntityTypes(): array
    {
        return array_filter(
            $this->entityTypeManager->getDefinitions(),
            function (EntityTypeInterface $entityType): bool {
                return $entityType->entityClassImplements(FieldableEntityInterface::class)
                    && $entityType->hasKey('published')
                    && isset($this->entityFieldManager->getFieldStorageDefinitions($entityType->id())[$entityType->getKey('published')])
                    && isset($this->entityFieldManager->getFieldStorageDefinitions($entityType->id())['field_meta']);
            }
        );
    }
}
