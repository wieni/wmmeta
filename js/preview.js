(function ($, Drupal) {
    'use strict';

    const hasPathField = () => !!$('.field--type-path').length;
    const hasPathAuto = () => hasPathField() && $('#edit-path-0-pathauto').is(':checked');

    const getSettings = (settings) => {
        const seoPreviewSettings = $.extend(true, {}, settings.wmmeta.seoPreview.settings);

        if (hasPathAuto() || (hasPathField() && !hasPathAuto())) {
            seoPreviewSettings.metadata.url.full_url = $('#edit-path-0-alias');
        }

        const $image = $('[data-drupal-selector="edit-field-meta-0-inline-entity-form-field-meta-image-container-table-0"] > td:first-of-type img');
        let image = seoPreviewSettings.facebook.featured_image;

        if ($image.length) {
            image = $image.attr('src');
        }

        // Fixes issue with passing a string to facebook.featured_image
        seoPreviewSettings.facebook.featured_image = $('<input type="text" value="' + image + '">');

        const $title = $('#edit-title-0-value');
        if ($title && $title.length) {
            seoPreviewSettings.metadata.title = $title;
        }

        const $desc = $('#edit-field-meta-0-inline-entity-form-field-meta-description-0-value');
        if ($desc && $desc.length) {
            seoPreviewSettings.metadata.desc = $desc;
        }

        return seoPreviewSettings;
    };

    const observer = new MutationObserver(() => {
        $.seoPreview(getSettings(window.drupalSettings));
    });

    Drupal.behaviors.wmmetaPreview = {
        attach: function (context, settings) {
            const $preview = $('#wmmeta-preview');

            if ($preview.attr('data-processed')) {
                return;
            }

            $.seoPreview(getSettings(settings));

            observer.observe(document.querySelector('.field--name-field-meta-image > .form-item'), {
                childList: true,
                subtree: true,
            });

            $preview.attr('data-processed', true);
        },
    };

}(jQuery, Drupal));
