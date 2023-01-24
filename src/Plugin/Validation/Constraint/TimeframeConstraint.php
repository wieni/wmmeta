<?php

namespace Drupal\wmmeta\Plugin\Validation\Constraint;

use Drupal\Core\Entity\Plugin\Validation\Constraint\CompositeConstraintBase;
use Symfony\Component\Validator\Constraint;

/**
 * @Constraint(
 *     id = "timeframe_constraint",
 *     label = @Translation("Timeframe", context = "Validation"),
 *     type = "entity:meta"
 * )
 */
class TimeframeConstraint extends CompositeConstraintBase
{
    public $invalidTimeframe = 'The publish and unpublish dates do not form a valid timeframe';

    public function coversFields()
    {
        return ['field_publish_on', 'field_unpublish_on'];
    }
}
