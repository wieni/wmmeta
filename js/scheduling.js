(function ($, Drupal) {
    'use strict';

    Drupal.behaviors.wmmeta_scheduling = {
        attach: function (context, settings) {
            let schedulerStatusField = $('select[name="field_meta[0][inline_entity_form][field_publish_status]"]');

            schedulerStatusField.change(function() {
                let originalStatus = settings.wmmeta.scheduling.status;

                if (originalStatus !== 'published') {
                    return;
                }

                let currentStatus = $(this).val();

                if (currentStatus !== 'scheduled') {
                    return;
                }

                let schedulerPublishOnDateField = $('input[name="field_meta[0][inline_entity_form][field_publish_on][0][value][date]"]');
                let schedulerPublishOnTimeField = $('input[name="field_meta[0][inline_entity_form][field_publish_on][0][value][time]"]');
                schedulerPublishOnDateField.val(settings.wmmeta.scheduling.created_date);
                schedulerPublishOnTimeField.val(settings.wmmeta.scheduling.created_time);
            });
        }
    };

}(jQuery, Drupal));
