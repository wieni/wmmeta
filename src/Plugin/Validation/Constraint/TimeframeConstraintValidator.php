<?php

namespace Drupal\wmmeta\Plugin\Validation\Constraint;

use Drupal\wmmeta\Entity\Meta\Meta;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the meta timeframe constraint.
 */
class TimeframeConstraintValidator extends ConstraintValidator
{
    public function validate($meta, Constraint $constraint)
    {
        if (!$constraint instanceof TimeframeConstraint) {
            return;
        }

        if (!$meta instanceof Meta) {
            return;
        }

        if (
            $meta->getPublishedStatus() !== Meta::SCHEDULED
            || !$meta->getUnpublishOn()
            || !$meta->getPublishOn()
            || $meta->getPublishOn() < $meta->getUnpublishOn()
        ) {
            return;
        }

        $this->context
            ->buildViolation($constraint->invalidTimeframe)
            ->atPath('field_unpublish_on')
            ->addViolation();
    }
}
