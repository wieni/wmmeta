(function ($, Drupal) {
    'use strict';

    const hasPathField = () => !!$('.field--type-path').length;
    const hasPathAuto = () => hasPathField() && $('#edit-path-0-pathauto').is(':checked');

    Drupal.behaviors.wmmetaPreview = {
        attach: function (context, settings) {
            let image = '';
            const url = {};
            const $preview = $('#wmmeta-preview');

            if ($preview.attr('data-processed')) {
                return;
            }

            if (hasPathField() && !hasPathAuto()) {
                url.full_url = $('#edit-path-0-alias');
            } else if (hasPathAuto()) {
                url.full_url = $('#edit-path-0-alias');
                url.use_slug = true;
                url.base_domain = window.location.origin;
            }

            image = $('[data-drupal-selector="edit-field-meta-0-inline-entity-form-field-meta-image-container-table-0"] > td:first-of-type > a').attr('href');
            image = $('<input type="text" value="' + image + '">');

            $.seoPreview({
                google_div: '#seopreview-google',
                facebook_div: '#seopreview-facebook',
                metadata: {
                    title: $('#edit-title-0-value'),
                    desc: $('#edit-field-meta-0-inline-entity-form-field-meta-description-0-value'),
                    url: url,
                },
                facebook: {
                    featured_image: image,
                },
            });

            $preview.attr('data-processed', true);
        },
    };

}(jQuery, Drupal));
