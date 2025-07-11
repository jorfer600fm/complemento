<?php
/**
 * Archivo para alojar los shortcodes personalizados del tema hijo.
 * v1.0.0
 *
 * @package Astra Child Theme
 */

// Evitar el acceso directo.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Shortcode [avisos_recientes].
 * Muestra los 5 últimos avisos con pago confirmado.
 */
function ap_shortcode_avisos_recientes() {
    $args = [
        'post_type'      => 'aviso',
        'post_status'    => 'publish',
        'posts_per_page' => 5,
        'orderby'        => 'date',
        'order'          => 'DESC',
        'meta_query'     => [
            [
                'key'     => '_ap_pago_confirmado',
                'value'   => 'yes',
                'compare' => '=',
            ],
        ],
    ];

    $recent_posts_query = new WP_Query( $args );

    ob_start();

    if ( $recent_posts_query->have_posts() ) {
        echo '<div class="ap-results-list">'; // Usamos la misma clase que en los resultados de búsqueda

        while ( $recent_posts_query->have_posts() ) {
            $recent_posts_query->the_post();
            
            // Reutilizamos la misma estructura de tarjeta de la página search.php
            $post_id = get_the_ID();
            $price = get_post_meta($post_id, 'ap_price', true);
            $unit = get_post_meta($post_id, 'ap_unit', true);
            $destacado = get_post_meta($post_id, 'ap_distintivo_1', true);
            $verificado = get_post_meta($post_id, 'ap_distintivo_2', true);
            $departamento = strip_tags(get_the_term_list($post_id, 'departamento', '', ', ', ''));
            ?>
            <article class="ap-result-card">
                <a href="<?php the_permalink(); ?>" class="ap-card-link">
                    <div class="ap-card-thumb">
                        <?php if (has_post_thumbnail()) : ?>
                            <?php the_post_thumbnail('medium_large'); ?>
                        <?php else : ?>
                            <img src="<?php echo esc_url(AP_PLUGIN_URL . 'public/img/placeholder.png'); ?>" alt="Imagen no disponible">
                        <?php endif; ?>
                    </div>
                    <div class="ap-card-content">
                        <h2 class="ap-card-title"><?php the_title(); ?></h2>
                        <div class="ap-card-excerpt">
                            <?php the_excerpt(); ?>
                        </div>
                        <div class="ap-card-footer">
                            <div class="ap-footer-main-line">
                                <div class="ap-card-price">
                                    <?php if (!empty($price)) : ?>
                                        S/ <?php echo number_format(floatval($price), 0, '.', ','); ?>
                                        <?php if (!empty($unit)) : ?>
                                            <span>(<?php echo esc_html($unit); ?>)</span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                                <div class="ap-card-location">
                                    <?php if (!empty($departamento)) : ?>
                                        📍 <?php echo esc_html($departamento); ?>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <?php if (!empty($destacado) || !empty($verificado)) : ?>
                            <div class="ap-card-badges">
                                <?php if (!empty($destacado)) : ?>
                                    <span class="ap-badge-destacado">⭐ <?php echo esc_html($destacado); ?></span>
                                <?php endif; ?>
                                <?php if (!empty($verificado)) : ?>
                                    <span class="ap-badge-verificado">✅ <?php echo esc_html($verificado); ?></span>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </a>
            </article>
            <?php
        }
        echo '</div>';
    } else {
        // Opcional: Si no hay avisos que mostrar, no muestra nada.
        // echo '<p>No hay avisos recientes para mostrar.</p>';
    }

    wp_reset_postdata();

    return ob_get_clean();
}
add_shortcode( 'avisos_recientes', 'ap_shortcode_avisos_recientes' );