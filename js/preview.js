(function ($, Drupal) {
    'use strict';

    const hasPathField = () => !!$('.field--type-path').length;
    const hasPathAuto = () => hasPathField() && $('#edit-path-0-pathauto').is(':checked');

    Drupal.behaviors.wmmetaPreview = {
        attach: function (context, settings) {
            console.log('hey');
            const seoPreviewSettings = $.extend(true, {}, settings.wmmeta.seoPreview.settings);
            const $preview = $('#wmmeta-preview');

            if ($preview.attr('data-processed')) {
                return;
            }

            if (hasPathAuto() || (hasPathField() && !hasPathAuto())) {
                seoPreviewSettings.metadata.url.full_url = $('#edit-path-0-alias');
            }

            const $image = $('[data-drupal-selector="edit-field-meta-0-inline-entity-form-field-meta-image-container-table-0"] > td:first-of-type img');
            let image = seoPreviewSettings.facebook.featured_image;
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

            $.seoPreview(seoPreviewSettings);

            $preview.attr('data-processed', true);
        },
    };

}(jQuery, Drupal));
