<?php

namespace Drupal\wmmeta;

final class WmmetaEvents
{
    /**
     * Allows altering the field_meta inline entity form
     *
     * The event object is an instance of
     * @uses \Drupal\wmmeta\Event\MetaFormAlterEvent
     */
    public const META_FORM_ALTER = 'wmmeta.field_meta_inline_entity_form.alter';
}
