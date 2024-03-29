<?php

namespace Drupal\wmmeta\Service\SchemaOrg\Provider;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Url;
use Spatie\SchemaOrg\BaseType;
use Spatie\SchemaOrg\Schema;
use Symfony\Component\Routing\Route;

class OrganisationSchemaProvider implements SchemaProviderInterface
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
        $organization = Schema::organization()
            ->name($this->getName())
            ->url($this->getWebsite())
            ->sameAs($this->getSameAs());

        if ($logo = $this->getLogo()) {
            $organization->logo($logo);
        }

        return $organization;
    }

    /** The name of the organization */
    protected function getName(): string
    {
        return $this->config
            ->get('system.site')
            ->get('name');
    }

    /** The URL for the organization's official website. */
    protected function getWebsite(): string
    {
        return Url::fromRoute('<front>')
            ->setAbsolute()
            ->toString();
    }

    /**
     * An array of URLs for the organization's official social media profile pages.
     * @see https://developers.google.com/search/docs/data-types/social-profile
     * @return string[]
     */
    protected function getSameAs(): array
    {
        return [];
    }

    /** The logo of the organization. */
    protected function getLogo(): string
    {
        return '';
    }
}
