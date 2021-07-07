<?php

namespace Drupal\wmmeta\Service;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class UrlHelper
{
    /** @var EntityTypeManagerInterface */
    protected $entityTypeManager;
    /** @var PathValidatorInterface */
    protected $pathValidator;
    /** @var RouteProviderInterface */
    protected $routeProvider;
    /** @var LanguageManagerInterface */
    protected $languageManager;
    /** @var Request */
    protected $currentRequest;

    public function __construct(
        EntityTypeManagerInterface $entityTypeManager,
        PathValidatorInterface $pathValidator,
        RouteProviderInterface $routeProvider,
        LanguageManagerInterface $languageManager,
        RequestStack $requestStack
    ) {
        $this->entityTypeManager = $entityTypeManager;
        $this->pathValidator = $pathValidator;
        $this->routeProvider = $routeProvider;
        $this->languageManager = $languageManager;
        $this->currentRequest = $requestStack->getCurrentRequest();
    }

    public function getRefererEntity(): ?EntityInterface
    {
        $refererUrl = $this->currentRequest->server->get('HTTP_REFERER');
        $refererRequest = Request::create($refererUrl);
        $url = $this->pathValidator->getUrlIfValid($refererRequest->getRequestUri());

        if (!$url instanceof Url) {
            return null;
        }

        return $this->getEntityByUrl($url);
    }

    public function getEntityByRoute(string $routeName, array $routeParameters): ?EntityInterface
    {
        return $this->getEntityByUrl(
            Url::fromRoute($routeName, $routeParameters)
        );
    }

    /** @return EntityInterface|null */
    public function getEntityByUrl(Url $url)
    {
        $entityTypeId = null;
        $entityId = null;

        if ($url->isExternal() || !$url->isRouted()) {
            return null;
        }

        $route = $this->routeProvider->getRouteByName($url->getRouteName());
        $language = $url->getOption('language') ?? $this->languageManager->getCurrentLanguage(LanguageInterface::TYPE_URL);

        if ($entityForm = $route->getDefault('_entity_form')) {
            [$entityTypeId] = explode('.', $entityForm);
            $entityId = $url->getRouteParameters()[$entityTypeId];
        } elseif ($entityAccess = $route->getRequirement('_entity_access')) {
            [$entityTypeId] = explode('.', $entityAccess);
            $entityId = $url->getRouteParameters()[$entityTypeId];
        }

        if ($entityTypeId && $entityId) {
            $entity = $this->entityTypeManager
                ->getStorage($entityTypeId)
                ->load($entityId);

            if ($entity->get('langcode')->value === $language->getId()) {
                return $entity;
            }

            if ($entity->hasTranslation($language->getId())) {
                return $entity->getTranslation($language->getId());
            }
        }

        return null;
    }
}
