<?php

namespace Drupal\wmmeta\Plugin\Action\Derivative;

use Drupal\Core\Action\Plugin\Action\Derivative\EntityActionDeriverBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\FieldableEntityInterface;

class EntityScheduleActionDeriver extends EntityActionDeriverBase
{
    protected function isApplicable(EntityTypeInterface $entityType): bool
    {
        return $entityType->entityClassImplements(FieldableEntityInterface::class);
    }
}
