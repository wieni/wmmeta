<?php

namespace Drupal\wmmeta\EventSubscriber;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\wmmeta\Entity\Eck\Meta\Meta;
use Drupal\wmmeta\Event\MetaFormAlterEvent;
use Drupal\wmmeta\WmmetaEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MetaFormAlterSubscriber implements EventSubscriberInterface
{
    use StringTranslationTrait;

    public static function getSubscribedEvents()
    {
        return [
            WmmetaEvents::META_FORM_ALTER => 'formAlter',
        ];
    }

    public function formAlter(MetaFormAlterEvent $event)
    {
        $form = &$event->getForm();

        $this->addSeoPreview($form);
        $form['#after_build'][] = [static::class, 'addSchedulingFieldsStates'];
    }

    protected function addSeoPreview(array $form): array
    {
        $form['seo_preview'] = [
            '#type' => 'item',
            '#title' => $this->t('Metadata preview'),
            '#description' => t('Show a live preview of Facebook & Google metadata.'),
            '#description_display' => 'after',
            '#markup' => sprintf(
                '<br><a href="%s" class="use-ajax button button--meta-preview" data-dialog-type="dialog">%s</a>',
                Url::fromRoute('wmmeta.preview_modal')->toString(),
                $this->t('Show preview')
            ),
            '#weight' => $form['field_meta_image']['#weight'],
        ];

        return $form;
    }

    public static function addSchedulingFieldsStates(array $form, FormStateInterface $formState): array
    {
        $entity = $formState->getFormObject()->getEntity();
        $status = $form['#entity']->getPublishedStatus();

        if ($status && $status !== Meta::SCHEDULED) {
            $form['field_publish_on']['widget'][0]['value']['#default_value'] = new DrupalDateTime('now');
        }

        $form['field_publish_on']['#states'] = [
            'visible' => [
                self::getInputSelector($form['field_publish_status']['widget']) => ['value' => Meta::SCHEDULED],
            ],
        ];
        $form['field_publish_on']['widget'][0]['#theme_wrappers'] = ['form_element'];

        $form['field_unpublish_on']['#states'] = [
            'visible' => [
                self::getInputSelector($form['field_publish_status']['widget']) => ['value' => Meta::SCHEDULED],
            ],
        ];
        $form['field_unpublish_on']['widget'][0]['#theme_wrappers'] = ['form_element'];

        $form['#attached']['library'][] = 'wmmeta/scheduling';
        $form['#attached']['drupalSettings']['wmmeta']['scheduling'] = [
            'status' => $status,
            'created_date' => $entity->getCreated()->format('Y-m-d'),
            'created_time' => $entity->getCreated()->format('H:i:s'),
        ];

        return $form;
    }

    protected static function getInputSelector(array $element)
    {
        return sprintf(
            ':input[name="%s[%s]"]',
            array_shift($element['#parents']),
            implode('][', $element['#parents'])
        );
    }
}
