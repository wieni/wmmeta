<?php

namespace Drupal\wmmeta;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;

class WmmetaServiceProvider implements ServiceModifierInterface
{
    public function alter(ContainerBuilder $container)
    {
        $modules = $container->getParameter('container.modules');

        if (!isset($modules['wmmedia'])) {
            $container->removeDefinition('wmmeta.media_usages_alter.subscriber');
        }
    }
}
