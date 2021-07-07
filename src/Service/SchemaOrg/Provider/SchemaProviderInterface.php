<?php

namespace Drupal\wmmeta\Service\SchemaOrg\Provider;

use Spatie\SchemaOrg\BaseType;
use Symfony\Component\Routing\Route;

interface SchemaProviderInterface
{
    /**
     * Determines if the provider applies to a specific route
     *
     * @param Route|null $route
     *   The route to consider attaching to.
     *
     * @return bool
     *   TRUE if the provider applies to the passed route, FALSE otherwise.
     */
    public function applies(Route $route): bool;

    public function getSchema(): BaseType;
}
