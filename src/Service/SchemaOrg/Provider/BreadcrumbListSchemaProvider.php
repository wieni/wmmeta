<?php

namespace Drupal\wmmeta\Service\SchemaOrg\Provider;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Spatie\SchemaOrg\BaseType;
use Spatie\SchemaOrg\Schema;
use Symfony\Component\Routing\Route;

class BreadcrumbListSchemaProvider implements SchemaProviderInterface
{
    /** @var RouteMatchInterface */
    protected $routeMatch;
    /** @var BreadcrumbBuilderInterface */
    protected $breadcrumbBuilder;
    /** @var array */
    protected $links;

    public function __construct(
        RouteMatchInterface $routeMatch,
        BreadcrumbBuilderInterface $breadcrumbBuilder
    ) {
        $this->routeMatch = $routeMatch;
        $this->breadcrumbBuilder = $breadcrumbBuilder;
    }

    public function applies(Route $route): bool
    {
        return !empty($this->getLinks());
    }

    public function getSchema(): BaseType
    {
        $links = $this->getLinks();

        $items = array_map(
            function (Link $link, $i) {
                $url = $link->getUrl()->setAbsolute()->toString();
                $text = $link->getText();

                if (!is_string($text) && !$text instanceof MarkupInterface) {
                    return null;
                }

                $item = Schema::webPage()
                    ->setProperty('@id', $url)
                    ->name((string) $text);

                return Schema::listItem()
                    ->position($i + 1)
                    ->item($item);
            },
            $links,
            array_keys($links)
        );

        $items = array_filter($items);

        return Schema::breadcrumbList()
            ->itemListElement($items);
    }

    protected function getLinks(): array
    {
        if (isset($this->links)) {
            return $this->links;
        }

        return $this->links = $this->breadcrumbBuilder->build($this->routeMatch)->getLinks();
    }
}
