<?php

namespace Drupal\wmmeta\Service\SchemaOrg\Provider;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Spatie\SchemaOrg\BaseType;
use Spatie\SchemaOrg\ListItem;
use Spatie\SchemaOrg\Schema;
use Symfony\Component\Routing\Route;

class BreadcrumbListSchemaProvider implements SchemaProviderInterface
{
    /** @var RouteMatchInterface */
    protected $routeMatch;
    /** @var BreadcrumbBuilderInterface */
    protected $breadcrumbBuilder;
    /** @var array */
    protected $listItems;

    public function __construct(
        RouteMatchInterface $routeMatch,
        BreadcrumbBuilderInterface $breadcrumbBuilder
    ) {
        $this->routeMatch = $routeMatch;
        $this->breadcrumbBuilder = $breadcrumbBuilder;
    }

    public function applies(Route $route): bool
    {
        return !empty($this->getListItems());
    }

    public function getSchema(): BaseType
    {
        return Schema::breadcrumbList()
            ->itemListElement($this->getListItems());
    }

    protected function getListItems(): array
    {
        if (isset($this->listItems)) {
            return $this->listItems;
        }

        $links = $this->breadcrumbBuilder->build($this->routeMatch)->getLinks();

        $listItems = array_map(
            static function (Link $link, $i): ?ListItem {
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

        return array_filter($listItems);
    }
}
