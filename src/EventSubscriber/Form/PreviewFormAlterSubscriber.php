<?php

namespace Drupal\wmmeta\EventSubscriber\Form;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Url;
use Drupal\hook_event_dispatcher\Event\Form\FormAlterEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PreviewFormAlterSubscriber implements EventSubscriberInterface
{
    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            'hook_event_dispatcher.form.alter' => 'formAlter',
        ];
    }

    public function formAlter(FormAlterEvent $event)
    {
        $form = &$event->getForm();
        $formObject = $event->getFormState()->getFormObject();

        if (!$this->hasMeta($formObject)) {
            return;
        }

        $form['actions']['seo_preview'] = [
            '#markup' => sprintf(
                '<a href="%s" class="use-ajax button button--meta-preview" data-dialog-type="dialog">%s</a>',
                Url::fromRoute('wmmeta.preview_modal')->toString(),
                t('Open meta preview')
            ),
            '#weight' => 20,
        ];
    }

    protected function hasMeta(FormInterface $formObject)
    {
        if (!$formObject instanceof EntityForm) {
            return false;
        }

        $entity = $formObject->getEntity();

        if (!$entity instanceof ContentEntityBase) {
            return false;
        }

        return $entity->hasField('field_meta');
    }
}
