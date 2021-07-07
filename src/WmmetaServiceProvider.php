<?php

namespace Drupal\wmmeta;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;
use Drupal\wmcontroller\WmcontrollerEvents;
use Drupal\wmmedia\WmmediaEvents;

class WmmetaServiceProvider implements ServiceModifierInterface
{
    public function alter(ContainerBuilder $container): void
    {
        $modules = $container->getParameter('container.modules');

        if (!isset($modules['wmmedia']) || !class_exists(WmmediaEvents::class)) {
            $container->removeDefinition('wmmeta.media_usages_alter.subscriber');
        }

        if (!isset($modules['wmcontroller']) || !class_exists(WmcontrollerEvents::class)) {
            $container->removeDefinition('wmmeta.subscriber');
        }
    }
}
