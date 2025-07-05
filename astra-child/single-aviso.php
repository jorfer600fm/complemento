<?php
/**
 * Plantilla para mostrar un único "Aviso".
 *
 * Esta plantilla estructura la página de detalle de un aviso, mostrando la galería,
 * descripción, video, mapa y una barra lateral con información de contacto y detalles.
 *
 * @package Astra Child Theme
 */

get_header(); 

// Obtener el ID del post actual.
$post_id = get_the_ID();

// Recopilar todos los datos del aviso en un array para un acceso más limpio.
$datos_aviso = [
    'titulo'         => get_the_title( $post_id ),
    'contenido'      => wpautop( get_the_content( null, false, $post_id ) ),
    'codigo'         => get_post_meta( $post_id, '_ap_aviso_codigo', true ),
    'precio'         => get_post_meta( $post_id, 'ap_price', true ),
    'unidad'         => get_post_meta( $post_id, 'ap_unit', true ),
    'distintivos'    => array_filter( [ // array_filter para eliminar valores vacíos
        get_post_meta( $post_id, 'ap_distintivo_1', true ),
        get_post_meta( $post_id, 'ap_distintivo_2', true ),
        get_post_meta( $post_id, 'ap_distintivo_3', true ),
    ] ),
    'nombre_contacto'=> get_post_meta( $post_id, 'ap_name', true ),
    'email'          => get_post_meta( $post_id, 'ap_email', true ),
    'telefono'       => get_post_meta( $post_id, 'ap_phone', true ),
    'whatsapp'       => get_post_meta( $post_id, 'ap_whatsapp', true ),
    'website'        => get_post_meta( $post_id, 'ap_website', true ),
    'direccion'      => get_post_meta( $post_id, 'ap_address', true ),
    'departamento'   => strip_tags( get_the_term_list( $post_id, 'departamento', '', ', ', '' ) ),
    'mapa'           => [
        'lat' => get_post_meta( $post_id, 'ap_map_lat', true ),
        'lng' => get_post_meta( $post_id, 'ap_map_lng', true ),
    ],
    'galeria_ids'    => [],
    'video_url'      => wp_get_attachment_url( get_post_meta( $post_id, 'ap_video', true ) ),
    'pdf_url'        => wp_get_attachment_url( get_post_meta( $post_id, 'ap_pdf', true ) ),
];

// Construir la galería de imágenes, empezando por la imagen destacada.
if ( has_post_thumbnail( $post_id ) ) {
    $datos_aviso['galeria_ids'][] = get_post_thumbnail_id( $post_id );
}
$galeria_extra_ids = [
    get_post_meta( $post_id, 'ap_photo_2', true ),
    get_post_meta( $post_id, 'ap_photo_3', true ),
];
foreach ( $galeria_extra_ids as $id ) {
    if ( ! empty( $id ) ) {
        $datos_aviso['galeria_ids'][] = $id;
    }
}
?>

<div id="primary" class="content-area primary">
    <main id="main" class="site-main">
        <div class="ast-container">
            <div class="ast-row">
                
                <div class="ast-col-lg-8 ast-col-md-12">
                    <article class="ap-aviso-container">
                        
                        <?php if ( ! empty( $datos_aviso['galeria_ids'] ) ) : ?>
                            <div class="ap-section ap-gallery">
                                <?php echo do_shortcode( '[gallery ids="' . implode( ',', $datos_aviso['galeria_ids'] ) . '" columns="3" link="file"]' ); ?>
                            </div>
                        <?php endif; ?>

                        <h1 class="ap-aviso-titulo"><?php echo esc_html( $datos_aviso['titulo'] ); ?></h1>

                        <?php if ( ! empty( $datos_aviso['distintivos'] ) ) : ?>
                            <div class="ap-distintivos-wrapper">
                                <?php foreach ( $datos_aviso['distintivos'] as $distintivo ) : ?>
                                    <span class="ap-distintivo"><?php echo esc_html( $distintivo ); ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <div class="ap-section ap-contenido">
                            <h3>Descripción</h3>
                            <?php echo $datos_aviso['contenido']; // Contenido ya procesado con wpautop ?>
                        </div>
                        
                        <?php if ( $datos_aviso['video_url'] ) : ?>
                             <div class="ap-section ap-video">
                                 <h3>Video</h3>
                                 <?php echo do_shortcode( '[video src="' . esc_url( $datos_aviso['video_url'] ) . '"]' ); ?>
                             </div>
                        <?php endif; ?>
                        
                        <?php if ( ! empty( $datos_aviso['mapa']['lat'] ) && ! empty( $datos_aviso['mapa']['lng'] ) ) : ?>
                            <div class="ap-section ap-mapa">
                                <h3>Ubicación</h3>
                                <div id="ap-map-display" style="height: 400px; width: 100%;"></div>
                                <script>
                                    document.addEventListener('DOMContentLoaded', function() {
                                        const lat = <?php echo esc_js( $datos_aviso['mapa']['lat'] ); ?>;
                                        const lng = <?php echo esc_js( $datos_aviso['mapa']['lng'] ); ?>;
                                        const map = L.map('ap-map-display').setView([lat, lng], 15);
                                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
                                        L.marker([lat, lng]).addTo(map);
                                    });
                                </script>
                            </div>
                        <?php endif; ?>

                    </article>
                </div>

                <div class="ast-col-lg-4 ast-col-md-12">
                    <aside class="ap-sidebar">

                        <?php if ( ! empty( $datos_aviso['precio'] ) ) : ?>
                            <div class="ap-precio-box">
                                <span class="ap-precio-valor">
                                    S/ <?php echo number_format( floatval( $datos_aviso['precio'] ), 0, '.', ',' ); ?>
                                </span>
                                <?php if ( ! empty( $datos_aviso['unidad'] ) ) : ?>
                                    <span class="ap-precio-unidad"><?php echo esc_html( $datos_aviso['unidad'] ); ?></span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <div class="ap-contact-box">
                            <h4>Información de Contacto</h4>
                            <p><strong>Publicado por:</strong> <?php echo esc_html( $datos_aviso['nombre_contacto'] ); ?></p>
                            
                            <?php if ( ! empty( $datos_aviso['telefono'] ) ) : ?>
                                <a href="tel:<?php echo esc_attr( $datos_aviso['telefono'] ); ?>" class="ap-contact-button ap-button-phone">
                                    Llamar: <?php echo esc_html( $datos_aviso['telefono'] ); ?>
                                </a>
                            <?php endif; ?>

                            <?php if ( ! empty( $datos_aviso['whatsapp'] ) ) : ?>
                                <a href="https://wa.me/51<?php echo esc_attr( $datos_aviso['whatsapp'] ); ?>" target="_blank" rel="noopener noreferrer" class="ap-contact-button ap-button-whatsapp">
                                    Contactar por WhatsApp
                                </a>
                            <?php endif; ?>

                             <?php if ( $datos_aviso['pdf_url'] ) : ?>
                                <a href="<?php echo esc_url( $datos_aviso['pdf_url'] ); ?>" target="_blank" rel="noopener noreferrer" class="ap-contact-button ap-button-pdf">
                                    Ver/Descargar PDF
                                </a>
                            <?php endif; ?>

                            <?php if ( ! empty( $datos_aviso['website'] ) ) : ?>
                                <a href="<?php echo esc_url( $datos_aviso['website'] ); ?>" target="_blank" rel="noopener noreferrer" class="ap-contact-button ap-button-website">
                                    Visitar Sitio Web
                                </a>
                            <?php endif; ?>
                        </div>

                        <div class="ap-details-box">
                            <h4>Detalles del Aviso</h4>
                            <ul>
                                <?php if ( ! empty( $datos_aviso['codigo'] ) ) : ?>
                                    <li><strong>Código:</strong> <?php echo esc_html( $datos_aviso['codigo'] ); ?></li>
                                <?php endif; ?>
                                <?php if ( ! empty( $datos_aviso['departamento'] ) ) : ?>
                                    <li><strong>Departamento:</strong> <?php echo esc_html( $datos_aviso['departamento'] ); ?></li>
                                <?php endif; ?>
                                <?php if ( ! empty( $datos_aviso['direccion'] ) ) : ?>
                                    <li><strong>Dirección:</strong> <?php echo esc_html( $datos_aviso['direccion'] ); ?></li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </aside>
                </div>

            </div> 
        </div>
    </main>
</div>

<?php 
get_footer(); 
?>