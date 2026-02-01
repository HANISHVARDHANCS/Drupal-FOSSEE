/**
 * @file
 * JavaScript for registration form - Event Registration Manager.
 */

(function ($, Drupal, drupalSettings) {
    'use strict';

    /**
     * Registration form enhancements.
     */
    Drupal.behaviors.eventRegistrationForm = {
        attach: function (context, settings) {
            // Enhanced select styling and behavior.
            $('.event-registration-select', context).once('registration-select').each(function () {
                var $select = $(this);

                // Add animation when value changes.
                $select.on('change', function () {
                    $(this).addClass('value-changed');
                    setTimeout(function () {
                        $select.removeClass('value-changed');
                    }, 300);
                });
            });

            // Form validation feedback enhancement.
            $('.event-registration-input', context).once('registration-input').each(function () {
                var $input = $(this);

                // Add visual feedback on focus.
                $input.on('focus', function () {
                    $(this).closest('.form-item').addClass('focused');
                });

                $input.on('blur', function () {
                    $(this).closest('.form-item').removeClass('focused');

                    // Add validated class if has value.
                    if ($(this).val()) {
                        $(this).closest('.form-item').addClass('has-value');
                    } else {
                        $(this).closest('.form-item').removeClass('has-value');
                    }
                });
            });

            // Smooth scroll to error messages.
            if ($('.messages--error', context).length) {
                $('html, body').animate({
                    scrollTop: $('.messages--error').first().offset().top - 100
                }, 500);
            }

            // Submit button loading state.
            $('form#event-registration-manager-registration-form', context).once('form-submit').on('submit', function () {
                var $button = $(this).find('.event-registration-submit');

                // Disable button and show loading state.
                $button
                    .prop('disabled', true)
                    .text(Drupal.t('Registering...'))
                    .addClass('is-loading');
            });

            // Cascade select reset - when category changes, reset dependent selects.
            $('#edit-category', context).once('category-change').on('change', function () {
                // The AJAX will handle populating, but we can add visual feedback.
                $('#edit-event-date, #edit-event-id').closest('.form-item').addClass('is-updating');

                setTimeout(function () {
                    $('#edit-event-date, #edit-event-id').closest('.form-item').removeClass('is-updating');
                }, 1000);
            });
        }
    };

})(jQuery, Drupal, drupalSettings);
