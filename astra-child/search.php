<?php
/**
 * Plantilla para mostrar los resultados de búsqueda de "Avisos".
 * v9.0.0 - Corrección final de alineación de precio/departamento.
 *
 * @package Astra Child Theme
 */

get_header(); 
?>

<div id="primary" class="content-area primary">
    <main id="main" class="site-main">
        <div class="ast-container">

            <div class="ap-results-list">
                <?php if (have_posts()) : ?>
                    <?php while (have_posts()) : the_post(); ?>
                        <?php
                            // Recopilar datos para la tarjeta
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
                    <?php endwhile; ?>
                    
                    <div class="ap-pagination">
                        <?php the_posts_pagination(); ?>
                    </div>

                <?php else : ?>
                    <div class="ap-no-results">
                        <h3>No se encontraron avisos que coincidan con tu búsqueda.</h3>
                        <p>Intenta con otras palabras clave o ajusta los filtros.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<?php get_sidebar(); ?>
<?php get_footer(); ?>