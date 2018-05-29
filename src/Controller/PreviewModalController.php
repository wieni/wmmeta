<?php

namespace Drupal\wmmeta\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenDialogCommand;
use Drupal\Core\Controller\ControllerBase;

class PreviewModalController extends ControllerBase {

    public function modal() {
        $content = [];
        $options = [
            'dialogClass' => 'wmmeta-preview-dialog',
            'width' => 550,
            'position' => [
                'my' => 'right bottom',
                'at' => 'right-10 bottom-10'
            ],
            'draggable' => true,
        ];

        $content['seo_preview'] = [
            '#type' => 'container',
            '#attached' => ['library' => ['wmmeta/preview']],
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
}
