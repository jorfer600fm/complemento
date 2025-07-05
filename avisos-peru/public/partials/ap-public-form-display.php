<?php
/**
 * Muestra el HTML del formulario de envío de avisos.
 * v6.3.0
 *
 * @package Avisos_Peru
 */
?>
<div class="ap-form-container">
    <form id="ap-new-listing-form" method="post" enctype="multipart/form-data">
        
        <div class="ap-form-field">
            <label for="ap_title">Título del Anuncio <span class="ap-required">*</span></label>
            <input type="text" id="ap_title" name="title" required>
            <small>Palabras restantes: <span id="ap_title_word_counter">15</span></small>
        </div>
        
        <div class="ap-form-field">
            <label for="ap_message">Mensaje <span class="ap-required">*</span></label>
            <textarea id="ap_message" name="message" rows="6" required></textarea>
            <small>Palabras restantes: <span id="ap_message_word_counter">150</span></small>
            <button type="button" id="ap-ai-button" disabled>💡 Ayuda IA para Mensaje</button>
        </div>

        <div class="ap-form-field">
            <label>Tipo de anuncio (Opcional)</label>
            <div class="ap-choice-switch">
                <label>
                    <input type="checkbox" name="ad_type[]" value="ofrezco">
                    <span>Ofrezco / Vendo</span>
                </label>
                <label>
                    <input type="checkbox" name="ad_type[]" value="busco">
                    <span>Busco / Compro</span>
                </label>
            </div>
        </div>

        <div class="ap-form-row">
            <div class="ap-form-field">
                <label for="ap_price">Precio (S/) (Opcional)</label>
                <input type="text" id="ap_price" name="price" inputmode="decimal" placeholder="Ej: 15,000.50">
            </div>
            <div class="ap-form-field">
                <label for="ap_unit">Unidad (Opcional)</label>
                <input type="text" id="ap_unit" name="unit" placeholder="Ej: kilo, metro, hora, servicio, etc.">
            </div>
        </div>

        <hr>
        <h4>Información de Contacto</h4>

        <div class="ap-form-field">
            <label for="ap_name">Sólo primer nombre y primer apellido <span class="ap-required">*</span></label>
            <input type="text" id="ap_name" name="name" required>
        </div>

        <div class="ap-form-row">
            <div class="ap-form-field">
                <label for="ap_email">Email de Contacto <span class="ap-required">*</span></label>
                <input type="email" id="ap_email" name="email" required>
            </div>
            <div class="ap-form-field">
                <label for="ap_phone">Celular de Contacto <span class="ap-required">*</span></label>
                <input type="tel" id="ap_phone" name="phone" required pattern="[0-9]{9}" title="Debe ser un número de 9 dígitos.">
            </div>
        </div>

        <div class="ap-form-field">
            <label for="ap_whatsapp">Número de WhatsApp (Opcional)</label>
            <input type="tel" id="ap_whatsapp" name="whatsapp" pattern="[0-9]{9}" title="Debe ser un número de 9 dígitos.">
        </div>
        
        <div class="ap-form-field">
            <label for="ap_website">Sitio Web (Opcional)</label>
            <input type="url" id="ap_website" name="website" placeholder="https://ejemplo.com">
        </div>
        
        <hr>
        <h4>Ubicación y Vigencia</h4>
        
        <div class="ap-form-row">
            <div class="ap-form-field">
                <label for="ap_department">Departamento <span class="ap-required">*</span></label>
                <select id="ap_department" name="department" required>
                    <option value="">Selecciona un departamento</option>
                    <?php
                    $departamentos = ['Amazonas', 'Áncash', 'Apurímac', 'Arequipa', 'Ayacucho', 'Cajamarca', 'Callao', 'Cusco', 'Huancavelica', 'Huánuco', 'Ica', 'Junín', 'La Libertad', 'Lambayeque', 'Lima', 'Loreto', 'Madre de Dios', 'Moquegua', 'Pasco', 'Piura', 'Puno', 'San Martín', 'Tacna', 'Tumbes', 'Ucayali'];
                    foreach ($departamentos as $dept) { echo "<option value='{$dept}'>{$dept}</option>"; }
                    ?>
                </select>
            </div>
            <div class="ap-form-field">
                <label for="ap_address">Dirección de referencia (Opcional)</label>
                <input type="text" id="ap_address" name="address">
            </div>
        </div>
        
        <div class="ap-form-field">
            <label>
                <input type="checkbox" id="ap_show_map_checkbox">
                <span>Marcar ubicación en el mapa (opcional)</span>
            </label>
            <div id="ap-map-container" style="display:none;">
                 <div id="ap-map" style="height: 300px; margin-top: 10px;"></div>
                 <div style="margin-top:10px;">
                     <button type="button" id="ap_confirm_map_coords">Confirmar Ubicación</button>
                     <span id="ap-map-feedback" class="ap-map-feedback-message"></span>
                 </div>
                 <input type="hidden" name="map_lat" id="ap_map_lat">
                 <input type="hidden" name="map_lng" id="ap_map_lng">
            </div>
        </div>
        
        <div class="ap-form-field">
             <label for="ap_expiry_date">Fecha de Vencimiento <span class="ap-required">*</span></label>
             <input type="date" id="ap_expiry_date" name="expiry_date" required>
             <small>Nota: de preferencia seleccionar menos de 30 días</small>
        </div>

        <hr>
        <h4>Archivos Multimedia (Opcional)</h4>
        
        <div class="ap-form-field ap-file-upload-wrapper">
            <label for="ap_photo_1" class="ap-file-label">Foto Principal</label>
            <input type="file" id="ap_photo_1" name="photo_1" accept="image/jpeg, image/png, image/webp">
            <div class="ap-file-info-placeholder"></div>
        </div>
        <div class="ap-form-field ap-file-upload-wrapper">
            <label for="ap_photo_2" class="ap-file-label">Foto 2</label>
            <input type="file" id="ap_photo_2" name="photo_2" accept="image/jpeg, image/png, image/webp">
            <div class="ap-file-info-placeholder"></div>
        </div>
        <div class="ap-form-field ap-file-upload-wrapper">
            <label for="ap_photo_3" class="ap-file-label">Foto 3</label>
            <input type="file" id="ap_photo_3" name="photo_3" accept="image/jpeg, image/png, image/webp">
            <div class="ap-file-info-placeholder"></div>
        </div>

        <div class="ap-form-field ap-file-upload-wrapper">
            <label for="ap_pdf" class="ap-file-label">Adjuntar PDF</label>
            <input type="file" id="ap_pdf" name="pdf" accept="application/pdf">
            <div class="ap-file-info-placeholder"></div>
            <small>El tamaño del archivo no debe superar los 200 KB.</small>
        </div>
        
        <div class="ap-form-field ap-file-upload-wrapper">
            <label for="ap_video" class="ap-file-label">Subir Video</label>
            <input type="file" id="ap_video" name="video" accept="video/*">
            <div class="ap-file-info-placeholder"></div>
            <small>No debe superar los 1500 KB (1.5 MB).</small>
        </div>
        
        <hr>

        <div class="ap-form-field ap-terms-field">
            <label>
                <input type="checkbox" id="ap_terms" name="terms" required> 
                <span>Acepto las condiciones de uso y políticas de privacidad <span class="ap-required">*</span></span>
            </label>
        </div>

        <div class="ap-form-field">
            <button type="submit" id="ap-submit-button" disabled>Publicar Anuncio</button>
            <small>Al enviar esta publicación usted acepta las condiciones de uso y políticas.</small>
        </div>
        
    </form>
    <div id="ap-form-feedback"></div>
</div>