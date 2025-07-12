<?php
/**
 * Plantilla para mostrar un único "Aviso".
 * v3.2 - Reestructuración de layout y textos mejorados.
 *
 * @package Astra Child Theme
 */

get_header();

// --- 1. RECOPILAR TODOS LOS DATOS ---
$post_id = get_the_ID();
$datos_aviso = [
    'titulo'       => get_the_title(),
    'autor'        => get_the_author(),
    'descripcion'  => get_the_content(),
    'precio'       => get_post_meta($post_id, 'ap_price', true),
    'unidad'       => get_post_meta($post_id, 'ap_unit', true),
    'telefono'     => get_post_meta($post_id, 'ap_phone', true),
    'whatsapp'     => get_post_meta($post_id, 'ap_whatsapp', true),
    'website'      => get_post_meta($post_id, 'ap_website', true),
    'pdf_url'      => wp_get_attachment_url(get_post_meta($post_id, 'ap_pdf', true)),
    'video_url'    => wp_get_attachment_url(get_post_meta($post_id, 'ap_video', true)),
    'departamento' => strip_tags(get_the_term_list($post_id, 'departamento', '', ', ', '')),
    'direccion'    => get_post_meta($post_id, 'ap_address', true),
    'mapa'         => [
        'lat' => get_post_meta($post_id, 'ap_map_lat', true),
        'lng' => get_post_meta($post_id, 'ap_map_lng', true),
    ],
    'distintivo_1' => get_post_meta($post_id, 'ap_distintivo_1', true),
    'distintivo_2' => get_post_meta($post_id, 'ap_distintivo_2', true),
    'galeria'      => [],
];

$video_player_html = '';
if ($datos_aviso['video_url']) {
    $video_player_html = wp_video_shortcode(['src' => esc_url($datos_aviso['video_url'])]);
}

if (has_post_thumbnail()) { $datos_aviso['galeria'][] = get_post_thumbnail_id(); }
foreach (['ap_photo_2', 'ap_photo_3'] as $key) {
    if ($img_id = get_post_meta($post_id, $key, true)) { $datos_aviso['galeria'][] = $img_id; }
}

// --- 2. RENDERIZAR LA PÁGINA ---
?>
<div id="primary" class="content-area primary">
    <main id="main" class="site-main">
        <div class="ast-container">
            <div class="ap-detalle-layout">

                <div class="ap-columna-principal">
                    <section class="ap-section ap-media-gallery">
                        <div id="ap-main-media-viewer">
                            <?php if ($video_player_html) : ?>
                                <?php echo $video_player_html; ?>
                            <?php elseif (!empty($datos_aviso['galeria'])) : ?>
                                <img src="<?php echo esc_url(wp_get_attachment_image_url($datos_aviso['galeria'][0], 'large')); ?>" alt="Vista principal">
                            <?php endif; ?>
                        </div>

                        <?php if ($video_player_html || count($datos_aviso['galeria']) > 1) : ?>
                            <div class="ap-media-thumbnails">
                                <?php if ($video_player_html) : ?>
                                    <a href="#" class="ap-media-thumbnail" data-media-type="video">
                                        <img src="<?php echo esc_url(get_the_post_thumbnail_url($post_id, 'thumbnail')); ?>" alt="Video Thumbnail">
                                        <span class="ap-video-play-icon">&#9658;</span>
                                    </a>
                                <?php endif; ?>
                                <?php foreach ($datos_aviso['galeria'] as $img_id) : ?>
                                    <a href="#" class="ap-media-thumbnail" data-media-type="image" data-full-url="<?php echo esc_url(wp_get_attachment_image_url($img_id, 'large')); ?>">
                                        <img src="<?php echo esc_url(wp_get_attachment_image_url($img_id, 'thumbnail')); ?>" alt="Thumbnail">
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </section>

                    <?php if (!empty($datos_aviso['descripcion'])) : ?>
                    <section class="ap-section ap-descripcion">
                        <h3 class="ap-section-title">Descripción</h3>
                        <?php echo wpautop($datos_aviso['descripcion']); ?>
                    </section>
                    <?php endif; ?>

                    <section class="ap-section ap-detalles-lista">
                        <h3 class="ap-section-title">Detalles del Aviso</h3>
                        <ul>
                            <?php if ($datos_aviso['departamento']) : ?>
                                <li><strong>Departamento:</strong> <span><?php echo esc_html($datos_aviso['departamento']); ?></span></li>
                            <?php endif; ?>
                             <?php if ($datos_aviso['direccion']) : ?>
                                <li><strong>Dirección:</strong> <span><?php echo esc_html($datos_aviso['direccion']); ?></span></li>
                            <?php endif; ?>
                        </ul>
                    </section>

                    <?php if ($datos_aviso['mapa']['lat'] && $datos_aviso['mapa']['lng']) : ?>
                    <section class="ap-section ap-mapa">
                        <h3 class="ap-section-title">Ubicación</h3>
                        <div id="ap-map-display" style="height: 400px; width: 100%; border-radius: 8px;"></div>
                        <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                const lat = <?php echo esc_js($datos_aviso['mapa']['lat']); ?>;
                                const lng = <?php echo esc_js($datos_aviso['mapa']['lng']); ?>;
                                const map = L.map('ap-map-display').setView([lat, lng], 15);
                                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
                                L.marker([lat, lng]).addTo(map);
                            });
                        </script>
                        
                        <?php // CAMBIO: Anotación movida aquí ?>
                        <div class="ap-share-link-container">
                             <h4>Enlace para compartir en: Facebook, WhatsApp, y otras redes sociales</h4>
                             <input type="text" readonly value="<?php echo esc_url(get_permalink()); ?>" id="ap-share-link-input">
                        </div>
                    </section>
                    <?php endif; ?>

                </div>

                <div class="ap-columna-lateral">
                    <div class="ap-info-box">
                        <h1 class="ap-titulo-principal"><?php echo esc_html($datos_aviso['titulo']); ?></h1>
                        
                        <?php if ($datos_aviso['distintivo_1'] || $datos_aviso['distintivo_2']) : ?>
                            <div class="ap-badges-container">
                                <?php if ($datos_aviso['distintivo_1']) : ?>
                                    <span class="ap-badge-detalle ap-badge-destacado">⭐ <?php echo esc_html($datos_aviso['distintivo_1']); ?></span>
                                <?php endif; ?>
                                <?php if ($datos_aviso['distintivo_2']) : ?>
                                    <span class="ap-badge-detalle ap-badge-verificado">✅ <?php echo esc_html($datos_aviso['distintivo_2']); ?></span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($datos_aviso['precio']) : ?>
                            <div class="ap-precio">
                                S/ <?php echo number_format(floatval($datos_aviso['precio']), 2, '.', ','); ?>
                                <?php if ($datos_aviso['unidad']) : ?>
                                    <span class="ap-unidad">(<?php echo esc_html($datos_aviso['unidad']); ?>)</span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <p class="ap-autor">Publicado por: <strong><?php echo esc_html($datos_aviso['autor']); ?></strong></p>

                        <p class="ap-accion-inmediata">Para acción inmediata haz clic en los contactos</p>

                        <div class="ap-botones-contacto">
                            <?php if ($datos_aviso['whatsapp']) : ?>
                                <a href="https://wa.me/51<?php echo esc_attr($datos_aviso['whatsapp']); ?>?text=<?php echo urlencode('Hola, estoy interesado/a en el aviso "' . $datos_aviso['titulo'] . '" que vi en - chambaynegocios.com'); ?>" target="_blank" class="ap-btn-whatsapp">Contactar por WhatsApp (<?php echo esc_html($datos_aviso['whatsapp']); ?>)</a>
                            <?php endif; ?>

                            <?php if ($datos_aviso['telefono']) : ?>
                                <a href="tel:<?php echo esc_attr($datos_aviso['telefono']); ?>" class="ap-btn-llamar">Llamar (<?php echo esc_html($datos_aviso['telefono']); ?>)</a>
                            <?php endif; ?>

                             <?php if ($datos_aviso['pdf_url']) : ?>
                                <a href="<?php echo esc_url($datos_aviso['pdf_url']); ?>" target="_blank" rel="noopener noreferrer" class="ap-btn-pdf">Más detalles, ver / descargar PDF</a>
                            <?php endif; ?>

                            <?php if ($datos_aviso['website']) : ?>
                                <?php // CAMBIO: Texto del botón actualizado ?>
                                <a href="<?php echo esc_url($datos_aviso['website']); ?>" target="_blank" rel="noopener noreferrer" class="ap-btn-website">Visitar el sitio Web, YouTube o Facebook del anunciante</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </main>
</div>
<?php 
if ($video_player_html) {
    wp_register_script('child-single-aviso-js-vars', '');
    wp_enqueue_script('child-single-aviso-js-vars');
    wp_add_inline_script('child-single-aviso-js-vars', 'const ap_video_player_html = ' . json_encode($video_player_html) . ';');
}
get_footer(); 
?>