jQuery(document).ready(function($) {
    'use strict';

    const feedbackContainer = $('#ap-email-test-feedback');
    const testButton = $('#ap-test-email-btn');
    const clearLogButton = $('#ap-clear-log-btn');
    const spinner = testButton.siblings('.spinner');
    const clearLogSpinner = clearLogButton.siblings('.spinner');

    testButton.on('click', function() {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'ap_test_email_sending',
                nonce: $('#ap_diagnostics_nonce').val()
            },
            beforeSend: function() {
                testButton.prop('disabled', true);
                spinner.addClass('is-active');
                feedbackContainer.empty();
            },
            success: function(response) {
                let noticeClass = response.success ? 'notice-success' : 'notice-error';
                let message = `<div class="notice ${noticeClass} is-dismissible"><p>${response.data.message}</p></div>`;
                feedbackContainer.html(message);
            },
            error: function() {
                let message = '<div class="notice notice-error is-dismissible"><p>Ocurrió un error de comunicación con el servidor (AJAX).</p></div>';
                feedbackContainer.html(message);
            },
            complete: function() {
                testButton.prop('disabled', false);
                spinner.removeClass('is-active');
            }
        });
    });

    clearLogButton.on('click', function() {
        if (!confirm('¿Estás seguro de que quieres borrar el registro de errores? Esta acción es irreversible.')) {
            return;
        }

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'ap_clear_error_log',
                nonce: $('#ap_diagnostics_nonce').val()
            },
            beforeSend: function() {
                clearLogButton.prop('disabled', true);
                clearLogSpinner.addClass('is-active');
            },
            success: function(response) {
                if (response.success) {
                    $('.ap-error-log-container').html('<p style="padding: 15px;">Registro de errores limpiado con éxito.</p>');
                }
            },
            complete: function() {
                clearLogButton.prop('disabled', false);
                clearLogSpinner.removeClass('is-active');
            }
        });
    });

    // Para cerrar los avisos dinámicos
    feedbackContainer.on('click', '.is-dismissible .notice-dismiss', function() {
        $(this).closest('.is-dismissible').remove();
    });
});