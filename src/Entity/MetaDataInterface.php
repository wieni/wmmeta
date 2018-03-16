<?php

namespace Drupal\wmmeta\Entity;

interface MetaDataInterface
{
    public function toMetaOGArray(): array;
}
