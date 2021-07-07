<?php

namespace Drupal\wmmeta\Service\SchemaOrg\Provider;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Url;
use Spatie\SchemaOrg\BaseType;
use Spatie\SchemaOrg\Schema;
use Symfony\Component\Routing\Route;

class WebsiteSchemaProvider implements SchemaProviderInterface
{
    /** @var ConfigFactoryInterface */
    protected $config;

    public function __construct(
        ConfigFactoryInterface $config
    ) {
        $this->config = $config;
    }

    public function applies(Route $route): bool
    {
        return true;
    }

    public function getSchema(): BaseType
    {
        $schema = Schema::webSite()
            ->name($this->getName())
            ->url($this->getWebsite());

        if ($searchUrl = $this->getSearchUrl()) {
            $action = Schema::searchAction()
                ->target($this->getSearchUrl());

            if (strpos($searchUrl, '{search_term}') !== false) {
                $action->setProperty('query-input', 'required name=search_term');
            }

            $schema->potentialAction($action);
        }

        return $schema;
    }

    /** The name of the website */
    protected function getName(): string
    {
        return $this->config
            ->get('system.site')
            ->get('name');
    }

    /** The URL for the website. */
    protected function getWebsite(): string
    {
        return Url::fromRoute('<front>')
            ->setAbsolute()
            ->toString();
    }

    /**
     * An url to the search page of your website. Can contain a query string parameter
     * with a {search_term} placeholder, e.g. https://www.example.org/search?query={search_term}
     * @return string[]
     */
    protected function getSearchUrl(): string
    {
        return '';
    }
}
