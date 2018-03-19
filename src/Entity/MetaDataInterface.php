<?php

namespace Drupal\wmmeta\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\wmmeta\Entity\Eck\Meta\Meta;

interface MetaDataInterface extends ContentEntityInterface, EntityChangedInterface, EntityPublishedInterface
{
    public function toMetaOGArray(): array;
    public function getMeta(): Meta;
}
