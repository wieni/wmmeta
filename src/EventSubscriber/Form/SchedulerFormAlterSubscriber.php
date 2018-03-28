<?php

namespace Drupal\wmmeta\EventSubscriber\Form;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\FormStateInterface;
use Drupal\hook_event_dispatcher\Event\Form\BaseFormEvent;
use Drupal\node\Entity\Node;
use Drupal\node\NodeForm;
use Drupal\wmmeta\Entity\Eck\Meta\Meta;
use Drupal\wmmeta\Entity\EntityPublishedInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SchedulerFormAlterSubscriber implements EventSubscriberInterface
{
    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            'hook_event_dispatcher.form_base_inline_entity_form.alter' => 'inlineFormAlter',
        ];
    }

    /**
     * @param \Drupal\hook_event_dispatcher\Event\Form\BaseFormEvent $event
     */
    public function inlineFormAlter(BaseFormEvent $event)
    {
        $form = &$event->getForm();
        $formState = $event->getFormState();

        if (!$this->isInlineMetaForm($form)) {
            return;
        }

        /* @var Meta $meta */
        $meta = $form['#entity'];
        $status = $meta->getPublishedStatus();

        if ($status && $status !== Meta::SCHEDULED) {
            $form['field_publish_on']['widget'][0]['value']['#default_value'] = new DrupalDateTime('now');
        }

        $form['field_publish_on']['#states'] = [
            'visible' => [
                ':input[name="field_meta[0][inline_entity_form][field_publish_status]"]' => ['value' => Meta::SCHEDULED],
            ],
        ];
        $form['field_publish_on']['widget'][0]['#theme_wrappers'] = ['form_element'];

        $form['field_unpublish_on']['#states'] = [
            'visible' => [
                ':input[name="field_meta[0][inline_entity_form][field_publish_status]"]' => ['value' => Meta::SCHEDULED],
            ],
        ];
        $form['field_unpublish_on']['widget'][0]['#theme_wrappers'] = ['form_element'];

        $entity = $this->getParentFormEntity($formState);
        if (!$entity instanceof EntityPublishedInterface) {
            return;
        }

        $form['#attached']['library'][] = 'wmmeta/scheduling';
        $form['#attached']['drupalSettings']['wmmeta']['scheduling'] = [
            'status' => $status,
            'created_date' => $entity->getCreated()->format('Y-m-d'),
            'created_time' => $entity->getCreated()->format('H:i:s'),
        ];
    }

    /**
     * @param array $form
     * @return bool
     */
    private function isInlineMetaForm(array $form)
    {
        return
            $form['#entity_type'] == 'meta'
            && $form['#bundle'] == 'meta';
    }

    /**
     * @param \Drupal\Core\Form\FormStateInterface $formState
     * @return \Drupal\node\Entity\Node|null
     */
    private function getParentFormEntity(FormStateInterface $formState)
    {
        $buildInfo = $formState->getBuildInfo();
        if (empty($buildInfo['callback_object'])) {
            return null;
        }
        $callbackObject = $buildInfo['callback_object'];
        if (!$callbackObject instanceof NodeForm) {
            return null;
        }
        $node = $callbackObject->getEntity();
        if (!$node instanceof Node) {
            return null;
        }

        return $node;
    }
}
