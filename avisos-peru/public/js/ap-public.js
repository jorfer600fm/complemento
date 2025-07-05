/**
 * Avisos Perú - Formulario Público
 *
 * Gestiona todas las interacciones del formulario de envío de avisos,
 * incluyendo contadores de palabras, validaciones, mapa interactivo,
 * ayuda de IA y el envío final mediante AJAX.
 */
jQuery(function($) {
    'use strict';

    const form = $('#ap-new-listing-form');
    if (!form.length) return;

    const MAX_TITLE_WORDS = 15;
    const MAX_MESSAGE_WORDS = 150;
    
    // --- LÓGICA DEL MAPA ---
    let leafletMap = null;
    let leafletMarker = null;

    $('#ap_show_map_checkbox').on('change', function() {
        const mapContainer = $('#ap-map-container');
        if ($(this).is(':checked')) {
            mapContainer.slideDown(400, function() {
                if (!leafletMap) {
                    leafletMap = L.map('ap-map').setView([-9.19, -75.01], 5);
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(leafletMap);
                    
                    leafletMap.on('click', function(e) {
                        const latLng = e.latlng;
                        if (leafletMarker) {
                            leafletMarker.setLatLng(latLng);
                        } else {
                            leafletMarker = L.marker(latLng).addTo(leafletMap);
                        }
                        $('#ap_map_lat').val(latLng.lat.toFixed(6));
                        $('#ap_map_lng').val(latLng.lng.toFixed(6));
                        $('#ap-map-feedback').empty(); // Limpiar feedback al cambiar ubicación
                    });
                }
                // Asegura que el mapa se renderice correctamente tras el slideDown
                setTimeout(() => leafletMap.invalidateSize(), 10);
            });
        } else {
            mapContainer.slideUp();
        }
    });

    $('#ap_confirm_map_coords').on('click', function() {
        if (leafletMarker) {
            const feedbackSpan = $('#ap-map-feedback');
            feedbackSpan.text('✓ Ubicación confirmada').fadeIn();
            setTimeout(() => feedbackSpan.fadeOut(() => feedbackSpan.empty()), 3000);
        } else {
            alert('Por favor, haz clic en el mapa para colocar un marcador primero.');
        }
    });

    // --- CONTADORES DE PALABRAS ---
    function updateWordCount(input, counter, maxWords) {
        const text = input.val().trim();
        const words = text === '' ? 0 : text.split(/\s+/).length;
        const remaining = maxWords - words;
        counter.text(remaining >= 0 ? remaining : 0).css('color', remaining < 0 ? 'red' : '');
    }

    $('#ap_title').on('input', () => updateWordCount($('#ap_title'), $('#ap_title_word_counter'), MAX_TITLE_WORDS));
    $('#ap_message').on('input', () => updateWordCount($('#ap_message'), $('#ap_message_word_counter'), MAX_MESSAGE_WORDS));
    
    // Inicializar contadores al cargar la página
    updateWordCount($('#ap_title'), $('#ap_title_word_counter'), MAX_TITLE_WORDS);
    updateWordCount($('#ap_message'), $('#ap_message_word_counter'), MAX_MESSAGE_WORDS);

    // --- GESTIÓN DE ESTADO DE BOTONES ---
    const submitButton = $('#ap-submit-button');
    const termsCheckbox = $('#ap_terms');
    const aiButton = $('#ap-ai-button');
    const titleInput = $('#ap_title');

    const updateSubmitButtonState = () => submitButton.prop('disabled', !termsCheckbox.is(':checked'));
    const updateAiButtonState = () => aiButton.prop('disabled', titleInput.val().trim() === '');

    termsCheckbox.on('change', updateSubmitButtonState);
    titleInput.on('input', updateAiButtonState);
    
    // Inicializar estado de botones
    updateSubmitButtonState();
    updateAiButtonState();

    // --- FORMATEO DE PRECIO ---
    $('#ap_price').on('input', function(e) {
        let value = e.target.value.replace(/[^0-9.]/g, '');
        let parts = value.split('.');
        if (parts.length > 2) {
            value = parts[0] + '.' + parts.slice(1).join('');
        }
        let [integerPart, decimalPart] = value.split('.');
        e.target.value = (decimalPart !== undefined) 
            ? integerPart.replace(/\B(?=(\d{3})+(?!\d))/g, ',') + '.' + decimalPart
            : integerPart.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    });

    // --- GESTIÓN DE SUBIDA DE ARCHIVOS ---
    form.on('change', 'input[type="file"]', function() {
        const placeholder = $(this).closest('.ap-file-upload-wrapper').find('.ap-file-info-placeholder');
        placeholder.empty();
        if (this.files.length > 0) {
            placeholder.html(`<div class="ap-file-info"><span>${this.files[0].name}</span><button type="button" class="ap-file-clear-btn">X</button></div>`);
        }
    });

    form.on('click', '.ap-file-clear-btn', function() {
        $(this).closest('.ap-file-upload-wrapper')
            .find('input[type="file"]').val('')
            .end().find('.ap-file-info-placeholder').empty();
    });

    // --- LÓGICA DE AYUDA IA ---
    aiButton.on('click', function() {
        if ($(this).is(':disabled')) return;
        
        $.ajax({
            url: ap_ajax_object.ajax_url,
            type: 'POST',
            data: { action: 'ap_get_ai_suggestion', nonce: ap_ajax_object.nonce, title: titleInput.val() },
            beforeSend: () => {
                aiButton.prop('disabled', true).text('Generando...');
                $('#ap-form-feedback').html('<div class="ap-spinner"></div>').show();
            },
            success: (r) => {
                if (r.success) {
                    $('#ap_message').val(r.data.text).focus();
                    updateWordCount($('#ap_message'), $('#ap_message_word_counter'), MAX_MESSAGE_WORDS);
                } else {
                    $('#ap-form-feedback').html(`<p class="ap-error">${r.data.message}</p>`);
                }
            },
            error: () => $('#ap-form-feedback').html('<p class="ap-error">Ocurrió un error inesperado. Inténtalo de nuevo.</p>'),
            complete: () => {
                updateAiButtonState();
                aiButton.text('💡 Ayuda IA');
                if (!$('#ap-form-feedback').find('p').hasClass('ap-error')) {
                    $('#ap-form-feedback').empty().hide();
                }
            }
        });
    });

    // --- ENVÍO DEL FORMULARIO ---
    form.on('submit', function(e) {
        e.preventDefault();
        $('#ap_price').val($('#ap_price').val().replace(/,/g, '')); // Limpiar comas del precio antes de enviar
        const formData = new FormData(this);
        formData.append('action', 'ap_submit_listing');
        formData.append('nonce', ap_ajax_object.nonce);

        $.ajax({
            url: ap_ajax_object.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: () => {
                submitButton.prop('disabled', true).text('Enviando...');
                $('#ap-form-feedback').html('<div class="ap-spinner"></div>').show();
            },
            success: (r) => {
                if (r.success) {
                    form.slideUp();
                    $('#ap-form-feedback').html(`<p class="ap-success">${r.data.message}</p>`).show();
                } else {
                    $('#ap-form-feedback').html(`<p class="ap-error">${r.data.message}</p>`).show();
                    updateSubmitButtonState();
                    submitButton.text('Publicar Anuncio');
                }
            },
            error: () => {
                $('#ap-form-feedback').html('<p class="ap-error">Ocurrió un error de red. Por favor, revisa tu conexión e inténtalo de nuevo.</p>').show();
                updateSubmitButtonState();
                submitButton.text('Publicar Anuncio');
            }
        });
    });
});