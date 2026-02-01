/**
 * @file
 * JavaScript for admin listing page - Event Registration Manager.
 */

(function ($, Drupal, drupalSettings) {
  'use strict';

  /**
   * Admin listing filter behavior.
   */
  Drupal.behaviors.eventRegistrationAdminListing = {
    attach: function (context, settings) {
      // Handle date filter change - update event names via AJAX.
      $('#filter-event-date', context).once('event-date-filter').on('change', function () {
        var selectedDate = $(this).val();
        var eventNameSelect = $('#filter-event-name');

        // Clear current options.
        eventNameSelect.empty();
        eventNameSelect.append($('<option>', {
          value: '',
          text: Drupal.t('- All Events -')
        }));

        if (selectedDate) {
          // Fetch event names for selected date.
          $.ajax({
            url: Drupal.url('admin/reports/event-registrations/ajax/event-names/' + encodeURIComponent(selectedDate)),
            type: 'GET',
            dataType: 'json',
            success: function (data) {
              $.each(data, function (key, value) {
                if (key !== '') {
                  eventNameSelect.append($('<option>', {
                    value: key,
                    text: value
                  }));
                }
              });
            },
            error: function () {
              console.error('Failed to fetch event names.');
            }
          });
        }
      });

      // Handle apply filters button.
      $('#apply-filters', context).once('apply-filters').on('click', function () {
        var eventDate = $('#filter-event-date').val();
        var eventName = $('#filter-event-name').val();

        var params = [];
        if (eventDate) {
          params.push('event_date=' + encodeURIComponent(eventDate));
        }
        if (eventName) {
          params.push('event_name=' + encodeURIComponent(eventName));
        }

        var newUrl = window.location.pathname;
        if (params.length > 0) {
          newUrl += '?' + params.join('&');
        }

        window.location.href = newUrl;
      });

      // Update export CSV link when filters change.
      function updateExportLink() {
        var eventDate = $('#filter-event-date').val();
        var eventName = $('#filter-event-name').val();

        var params = [];
        if (eventDate) {
          params.push('event_date=' + encodeURIComponent(eventDate));
        }
        if (eventName) {
          params.push('event_name=' + encodeURIComponent(eventName));
        }

        var exportUrl = Drupal.url('admin/reports/event-registrations/export');
        if (params.length > 0) {
          exportUrl += '?' + params.join('&');
        }

        $('#export-csv-btn').attr('href', exportUrl);
      }

      // Listen for filter changes.
      $('#filter-event-date, #filter-event-name', context).on('change', function () {
        updateExportLink();
      });
    }
  };

})(jQuery, Drupal, drupalSettings);
