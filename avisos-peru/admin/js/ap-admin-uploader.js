jQuery(document).ready(function($) {
    'use strict';

    $('body').on('click', '.ap-upload-button', function(e) {
        e.preventDefault();

        const button = $(this);
        const fieldId = button.data('field-id'); // Ej: 'ap_photo_2'
        
        const uploader = wp.media({
            title: 'Elegir Archivo',
            button: {
                text: 'Usar este archivo'
            },
            multiple: false
        }).on('select', function() {
            const attachment = uploader.state().get('selection').first().toJSON();
            $('#' + fieldId).val(attachment.id); // Guardamos el ID del adjunto

            let preview = '<p>' + attachment.filename + '</p>';
            if (attachment.type === 'image') {
                preview = '<img src="' + attachment.url + '" style="max-width:150px; height:auto;" />';
            } else {
                preview = '<a href="' + attachment.url + '" target="_blank">Ver Archivo: ' + attachment.filename + '</a>';
            }
            
            $('#' + fieldId + '_preview').html(preview).show();
            button.siblings('.ap-remove-button').show();

        }).open();
    });

    $('body').on('click', '.ap-remove-button', function(e) {
        e.preventDefault();
        
        const button = $(this);
        const fieldId = button.data('field-id');

        $('#' + fieldId).val('');
        $('#' + fieldId + '_preview').html('').hide();
        button.hide();
    });
});