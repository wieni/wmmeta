<?php

namespace Drupal\wmmeta\Service\SchemaOrg;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\AdminContext;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\hook_event_dispatcher\Event\Preprocess\HtmlPreprocessEvent;
use Drupal\wmmeta\Service\SchemaOrg\Provider\SchemaProviderInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SchemaOrgManager implements EventSubscriberInterface
{
    /** @var SchemaProviderInterface[] */
    protected $providers = [];

    /** @var ConfigFactoryInterface */
    protected $configFactory;
    /** @var RouteMatchInterface */
    protected $routeMatch;
    /** @var AdminContext */
    protected $adminContext;

    public static function getSubscribedEvents()
    {
        return [
            'preprocess_html' => 'onPreprocessHtml',
        ];
    }

    public function __construct(
        ConfigFactoryInterface $configFactory,
        RouteMatchInterface $routeMatch,
        AdminContext $adminContext
    ) {
        $this->configFactory = $configFactory;
        $this->routeMatch = $routeMatch;
        $this->adminContext = $adminContext;
    }

    public function addProvider(SchemaProviderInterface $provider)
    {
        $this->providers[] = $provider;

        return $this;
    }

    public function onPreprocessHtml(HtmlPreprocessEvent $event)
    {
        $elements = [];

        if (
            $this->adminContext->isAdminRoute()
            || !$this->configFactory->get('wmmeta.settings')->get('schema_org_tags')
        ) {
            return;
        }

        foreach ($this->providers as $provider) {
            if (
                !$provider instanceof SchemaProviderInterface
                || !$provider->applies($this->routeMatch->getRouteObject())
            ) {
                continue;
            }

            $schema = $provider->getSchema();

            $elements[] = [
                [
                    '#tag' => 'script',
                    '#attributes' => ['type' => 'application/ld+json'],
                    '#value' => json_encode($schema),
                ],
                sprintf('schema_org_%s', $schema->getType()),
            ];
        }

        $attached = $event->getVariables()->get('#attached', []);

        $attached['html_head'] = array_merge(
            $attached['html_head'] ?? [],
            $elements
        );

        $event->getVariables()->set('#attached', $attached);
    }
}
