<?php

namespace Drupal\wmmeta\EventSubscriber;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\wmmeta\Entity\Meta\Meta;
use Drupal\wmmeta\Event\MetaFormAlterEvent;
use Drupal\wmmeta\WmmetaEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MetaFormAlterSubscriber implements EventSubscriberInterface
{
    use StringTranslationTrait;

    public static function getSubscribedEvents(): array
    {
        return [
            WmmetaEvents::META_FORM_ALTER => 'formAlter',
        ];
    }

    public function formAlter(MetaFormAlterEvent $event): void
    {
        $form = &$event->getForm();

        $this->addSeoPreview($form);
        $form['#after_build'][] = [static::class, 'addSchedulingFieldsStates'];
        $form['#after_build'][] = [static::class, 'setPublishDateDefaultValue'];
    }

    public function addSeoPreview(array &$form): void
    {
        if (!isset($form['field_meta_description'], $form['field_meta_image'])) {
            return;
        }

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
    }

    public static function addSchedulingFieldsStates(array $form): array
    {
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

        return $form;
    }

    public static function setPublishDateDefaultValue(array $form): array
    {
        $publishOn = &$form['field_publish_on']['widget'][0]['value'];

        if (empty($publishOn['#default_value'])) {
            $publishOn['date']['#value'] = (new \DateTime())->format('Y-m-d');
            $publishOn['time']['#value'] = (new \DateTime())->format('H:i:s');
        }

        return $form;
    }

    protected static function getInputSelector(array $element): string
    {
        return sprintf(
            ':input[name="%s[%s]"]',
            array_shift($element['#parents']),
            implode('][', $element['#parents'])
        );
    }
}
