<?php

namespace Drupal\wmmeta\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenDialogCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\imgix\ImgixManagerInterface;
use Drupal\wmmeta\Service\UrlHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PreviewModalController extends ControllerBase
{
    /** @var UrlHelper */
    protected $urlHelper;
    /** @var ImgixManagerInterface */
    protected $imgixManager;

    public function __construct(
        UrlHelper $urlHelper,
        ImgixManagerInterface $imgixManager
    ) {
        $this->urlHelper = $urlHelper;
        $this->imgixManager = $imgixManager;
    }

    public function modal()
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
                    ]
                ]
            ],
            '#attributes' => [
                'id' => "wmmeta-preview",
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
                    'id' => "seopreview-{$container}",
                ],
            ];
        }

        $response = new AjaxResponse();
        $response->addCommand(new OpenDialogCommand('#drupal-modal', t('Preview'), $content, $options));

        return $response;
    }

    protected function getSettings()
    {
        $settings = [
            'google_div' => '#seopreview-google',
            'facebook_div' => '#seopreview-facebook',
        ];

        $entity = $this->urlHelper->getRefererEntity();

        if (!$entity) {
            return $settings;
        }

        $meta = $entity->get('field_meta')->entity;

        if (!$meta) {
            return $settings;
        }

        $settings['metadata']['title'] = $entity->label();
        $settings['metadata']['description'] = $meta->get('field_meta_description')->value;

        $url = $entity->toUrl()->setAbsolute()->toString();
        $urlParts = parse_url($url);
        $settings['metadata']['url'] = [
            'use_slug' => true,
            'full_url' => $urlParts['path'],
            'base_domain' => sprintf('%s://%s', $urlParts['scheme'], $urlParts['host']),
        ];

        if ($image = $meta->get('field_meta_image')->first()) {
            $imageUrl = $this->imgixManager->getImgixUrlByPreset($image->getFile(), 'default');
            $settings['facebook']['featured_image'] = $imageUrl;
        }

        return $settings;
    }

    public static function create(ContainerInterface $container)
    {
        return new static(
            $container->get('wmmeta.url_helper'),
            $container->get('imgix.manager')
        );
    }
}
