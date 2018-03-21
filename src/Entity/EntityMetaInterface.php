<?php

namespace Drupal\wmmeta\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\wmmeta\Entity\Eck\Meta\Meta;

interface EntityMetaInterface extends ContentEntityInterface
{
    public function toMetaOGArray(): array;
    public function getMeta(): Meta;
}
