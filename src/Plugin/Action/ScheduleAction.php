<?php

namespace Drupal\wmmeta\Plugin\Action;

use Drupal\Component\Utility\Html;
use Drupal\Core\Action\Plugin\Action\EntityActionBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\wmmeta\Entity\EntityPublishedInterface;
use Drupal\wmmeta\Entity\Meta\Meta;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @Action(
 *     id = \Drupal\wmmeta\Plugin\Action\ScheduleAction::ID,
 *     action_label = @Translation("Schedule"),
 *     deriver = "Drupal\wmmeta\Plugin\Action\Derivative\EntityScheduleActionDeriver",
 * )
 */
class ScheduleAction extends EntityActionBase implements PluginFormInterface
{
    public const ID = 'wmmeta:entity:schedule_action';

    /** @var PrivateTempStoreFactory */
    protected $privateTempStore;

    public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
    {
        $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
        $instance->privateTempStore = $container->get('tempstore.private');

        return $instance;
    }

    public function access($object, ?AccountInterface $account = null, $returnAsObject = false)
    {
        $result = $object->access('update', $account, true)
            ->andIf($object->get('field_meta')->access('edit', $account, true));

        return $returnAsObject ? $result : $result->isAllowed();
    }

    public function execute(?EntityInterface $entity = null): void
    {
        if (!$entity instanceof EntityPublishedInterface) {
            throw new \InvalidArgumentException(sprintf(
                'Expected an instance of %s. Got: %s',
                EntityPublishedInterface::class,
                get_class($entity)
            ));
        }

        $meta = $entity->getMeta();

        if ($publishDate = $this->configuration['publish_on']) {
            $date = $publishDate->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT);
            $meta->setPublishOn(\DateTime::createFromFormat(DateTimeItemInterface::DATETIME_STORAGE_FORMAT, $date));
        }

        if ($unpublishDate = $this->configuration['unpublish_on']) {
            $date = $unpublishDate->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT);
            $meta->setUnpublishOn(\DateTime::createFromFormat(DateTimeItemInterface::DATETIME_STORAGE_FORMAT, $date));
        }

        $meta->setPublishedStatus($this->configuration['status']);
        $meta->save();
        $entity->save();
    }

    public function buildConfigurationForm(array $form, FormStateInterface $formState): array
    {
        $status_id = Html::getUniqueId('bulk-actions__status__select');

        $form['status'] = [
            '#options' => ['' => $this->t('- Select a status -')] + Meta::getStatuses(),
            '#type' => 'select',
            '#attributes' => ['id' => [$status_id]],
        ];

        $form['publish_on'] = [
            '#attributes' => [
                'class' => ['publish-on'],
            ],
            '#states' => [
                'visible' => [
                    ':input[id="' . $status_id . '"]' => ['value' => Meta::SCHEDULED],
                ],
            ],
            '#type' => 'container',
            'publish_date' => [
                '#default_value' => '',
                '#title' => $this->t('Publish on'),
                '#type' => 'datetime',
            ],
        ];

        $form['unpublish_on'] = [
            '#attributes' => [
                'class' => ['unpublish-on'],
            ],
            '#states' => [
                'visible' => [
                    ':input[id="' . $status_id . '"]' => ['value' => Meta::SCHEDULED],
                ],
            ],
            '#type' => 'container',
            'unpublish_date' => [
                '#default_value' => '',
                '#title' => $this->t('Unpublish on'),
                '#type' => 'datetime',
            ],
        ];

        return $form;
    }

    public function validateConfigurationForm(array &$form, FormStateInterface $formState): void
    {
    }

    public function submitConfigurationForm(array &$form, FormStateInterface $formState): void
    {
        $this->configuration['status'] = $formState->getValue('status');
        $this->configuration['publish_on'] = $formState->getValue(['publish_on', 'publish_date']);
        $this->configuration['unpublish_on'] = $formState->getValue(['unpublish_on', 'unpublish_date']);
    }

    public static function getId(EntityTypeInterface $entityType): string
    {
        return sprintf('%s:%s', static::ID, $entityType->id());
    }
}
