<?php

namespace Drupal\wmmeta\Event;

use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\EventDispatcher\Event;

class MetaFormAlterEvent extends Event
{
    /** @var array */
    protected $form;
    /** @var FormStateInterface */
    protected $parentFormState;

    public function __construct(
        array &$form,
        FormStateInterface $parentFormState
    ) {
        $this->form = &$form;
        $this->parentFormState = $parentFormState;
    }

    /**
     * Get the inline entity form.
     *
     * @return array
     *   The entity form.
     */
    public function &getForm(): array
    {
        return $this->form;
    }

    /**
     * Get the form state of the parent form.
     *
     * @return FormStateInterface
     *   The form state.
     */
    public function getParentFormState(): FormStateInterface
    {
        return $this->parentFormState;
    }
}
