<?php

namespace Drupal\wmmeta\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenDialogCommand;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\file\FileInterface;
use Drupal\image\Entity\ImageStyle;
use Drupal\wmmedia\Plugin\Field\FieldType\MediaImageExtras;
use Drupal\wmmeta\Entity\EntityMetaInterface;
use Drupal\wmmeta\Service\MetaService;
use Drupal\wmmeta\Service\UrlHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PreviewModalController implements ContainerInjectionInterface
{
    /** @var UrlHelper */
    protected $urlHelper;
    /** @var MetaService */
    protected $metaService;

    public static function create(ContainerInterface $container)
    {
        $instance = new static();
        $instance->urlHelper = $container->get('wmmeta.url_helper');
        $instance->metaService = $container->get('wmmeta.meta');

        return $instance;
    }

    public function modal(): AjaxResponse
    {
        $content = [];
        $options = [
            'dialogClass' => 'wmmeta-preview-dialog',
            'width' => 550,
            'position' => [
                'my' => 'right bottom',
                'at' => 'right-10 bottom-10',
            ],
            'draggable' => true,
        ];

        $content['seo_preview'] = [
            '#type' => 'container',
            '#attached' => [
                'library' => ['wmmeta/preview'],
                'drupalSettings' => [
                    'wmmeta' => [
                        'seoPreview' => [
                            'settings' => $this->getSettings(),
                        ],
                    ],
                ],
            ],
            '#attributes' => [
                'id' => 'wmmeta-preview',
            ],
        ];

        foreach (['google', 'facebook'] as $i => $container) {
            $content['seo_preview'][$container] = [
                '#type' => 'container',
                '#weight' => $i,
            ];

            $content['seo_preview'][$container]['title'] = [
                '#markup' => sprintf('<h3>%s</h3>', ucfirst($container)),
                '#weight' => 1,
            ];

            $content['seo_preview'][$container]['container'] = [
                '#type' => 'container',
                '#weight' => 2,
                '#attributes' => [
                    'id' => sprintf('seopreview-%s', $container),
                ],
            ];
        }

        $response = new AjaxResponse();
        $response->addCommand(new OpenDialogCommand('#drupal-modal', t('Preview'), $content, $options));

        return $response;
    }

    protected function getSettings(): array
    {
        $settings = [
            'google_div' => '#seopreview-google',
            'facebook_div' => '#seopreview-facebook',
            'metadata' => [
                'title' => '',
                'desc' => '',
                'url' => [],
            ],
            'facebook' => [
                'featured_image' => '',
            ],
        ];

        $entity = $this->urlHelper->getRefererEntity();

        if (!$entity instanceof EntityMetaInterface) {
            return $settings;
        }

        if (!$meta = $entity->getMeta()) {
            return $settings;
        }

        if (!$meta->hasField('field_meta_description')) {
            throw new NotFoundHttpException('Meta does not have a description field');
        }

        if (!$meta->hasField('field_meta_image')) {
            throw new NotFoundHttpException('Meta does not have an image field');
        }

        // This is like calling `getEntityMetaData()`, but manually since it's not public.
        // We first save `MetaService` entity, get the metadata, and then restore the entity.
        $originalEntity = $this->metaService->getEntity();
        $this->metaService->setEntity($entity);
        $metaData = $this->metaService->getMetaData();
        $this->metaService->setEntity($originalEntity);

        $settings['metadata']['title'] = $metaData['title'] ?? $entity->label();
        $settings['metadata']['desc'] = $metaData['description'] ?? $meta->get('field_meta_description')->value;

        $url = $entity->toUrl()->setAbsolute()->toString();
        $urlParts = parse_url($url);
        $settings['metadata']['url'] = [
            'use_slug' => true,
            'full_url' => $urlParts['path'],
            'base_domain' => sprintf('%s://%s', $urlParts['scheme'], $urlParts['host']),
        ];

        $settings['facebook']['featured_image'] = $metaData['image'];

        return $settings;
    }

    protected function getImageUrl(FileInterface $file, string $imageStyleId): ?string
    {
        $path = $file->getFileUri();

        if (!$imageStyle = ImageStyle::load($imageStyleId)) {
            return null;
        }

        if (!$imageStyle->supportsUri($path)) {
            return null;
        }

        return $imageStyle->buildUrl($path);
    }
}
