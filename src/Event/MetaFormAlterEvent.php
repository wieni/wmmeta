<?php

namespace Drupal\wmmeta\Event;

use Symfony\Component\EventDispatcher\Event;

class MetaFormAlterEvent extends Event
{
    /** @var array */
    protected $form;

    public function __construct(array &$form)
    {
        $this->form = &$form;
    }

    /**
     * Get the inline entity form.
     *
     * @return array
     *   The usages.
     */
    public function &getForm(): array
    {
        return $this->form;
    }
}
