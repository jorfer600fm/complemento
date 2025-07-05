<?php
/**
 * Plantilla para mostrar los resultados de búsqueda de "Avisos".
 * v3.0.0 - Reconstrucción total del layout para un diseño moderno y funcional.
 *
 * @package Astra Child Theme
 */

get_header(); 

// Recopilar los valores de los filtros actuales para mantener su estado
$current_ad_types = isset($_GET['ad_type']) && is_array($_GET['ad_type']) ? array_map('sanitize_text_field', $_GET['ad_type']) : [];
$current_min_price = isset($_GET['min_price']) ? esc_attr(sanitize_text_field($_GET['min_price'])) : '';
$current_max_price = isset($_GET['max_price']) ? esc_attr(sanitize_text_field($_GET['max_price'])) : '';
$is_destacado = isset($_GET['destacado']) && $_GET['destacado'] === '1';
$is_verificado = isset($_GET['verificado']) && $_GET['verificado'] === '1';
?>

<div id="primary" class="content-area primary">
    <main id="main" class="site-main">
        <div class="ast-container">

            <header class="ap-results-header">
                <?php echo do_shortcode('[ap_aviso_buscador]'); ?>
            </header>

            <div class="ap-results-layout">

                <aside class="ap-results-sidebar">
                    <form method="get" action="<?php echo esc_url(home_url('/')); ?>" class="ap-filter-form">
                        <h3 class="ap-filter-title">Filtros</h3>
                        
                        <input type="hidden" name="s" value="<?php echo esc_attr(get_search_query()); ?>">
                        <input type="hidden" name="post_type" value="aviso">
                        <?php if (isset($_GET['departamento_slug']) && !empty($_GET['departamento_slug'])) : ?>
                            <input type="hidden" name="search_scope" value="department">
                            <input type="hidden" name="departamento_slug" value="<?php echo esc_attr($_GET['departamento_slug']); ?>">
                        <?php endif; ?>

                        <div class="ap-filter-block">
                            <h4>Tipo de Anuncio</h4>
                            <label><input type="checkbox" name="ad_type[]" value="ofrezco" <?php checked(in_array('ofrezco', $current_ad_types)); ?>> Ofrezco / Vendo</label>
                            <label><input type="checkbox" name="ad_type[]" value="busco" <?php checked(in_array('busco', $current_ad_types)); ?>> Busco / Compro</label>
                        </div>

                        <div class="ap-filter-block">
                            <h4>Filtrar por Precio (S/)</h4>
                            <div class="price-inputs">
                                <input type="text" name="min_price" id="min_price_filter" placeholder="Mínimo" value="<?php echo $current_min_price; ?>" inputmode="numeric">
                                <input type="text" name="max_price" id="max_price_filter" placeholder="Máximo" value="<?php echo $current_max_price; ?>" inputmode="numeric">
                            </div>
                        </div>

                        <div class="ap-filter-block">
                            <h4>Atributos Especiales</h4>
                            <label><input type="checkbox" name="destacado" value="1" <?php checked($is_destacado); ?>> ⭐ Solo avisos destacados</label>
                            <label><input type="checkbox" name="verificado" value="1" <?php checked($is_verificado); ?>> ✅ Solo negocios verificados</label>
                        </div>
                        
                        <button type="submit" class="ap-apply-filters-btn">Aplicar Filtros</button>
                    </form>
                </aside>

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
        </div>
    </main>
</div>

<?php get_footer(); ?>