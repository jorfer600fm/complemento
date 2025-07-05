<?php
/**
 * Gestiona la funcionalidad de búsqueda, incluyendo el shortcode del formulario.
 * v3.0.0 - Implementados filtros avanzados.
 *
 * @package Avisos_Peru
 */
class AP_Search {

    public function init() {
        add_shortcode('ap_aviso_buscador', [$this, 'render_search_form']);
        add_action('pre_get_posts', [$this, 'filter_search_query']);
    }

    public function render_search_form() {
        ob_start();
        $selected_department = get_query_var('departamento_slug');
        $is_department_scope = !empty($selected_department);
        ?>
        <div class="ap-search-form-container">
            <form role="search" method="get" class="ap-search-form" action="<?php echo esc_url(home_url('/')); ?>">
                <input type="hidden" name="post_type" value="aviso" />

                <div class="ap-search-scope-selector">
                    <label>
                        <input type="radio" name="search_scope" value="all" <?php checked(!$is_department_scope); ?>>
                        <span>Todo Perú</span>
                    </label>
                    <label>
                        <input type="radio" name="search_scope" value="department" <?php checked($is_department_scope); ?>>
                        <span>Por Departamento</span>
                    </label>
                </div>

                <div class="ap-search-main-panel">
                    <div class="ap-search-input-group">
                        <input type="search"
                               class="ap-search-field"
                               placeholder="Buscar: servicios, productos, anuncios y más"
                               value="<?php echo esc_attr(get_search_query()); ?>"
                               name="s"
                               title="Buscar:" />
                        <button type="submit" class="ap-search-submit">Buscar</button>
                    </div>

                    <div class="ap-department-wrapper" id="ap-department-wrapper" style="<?php echo $is_department_scope ? 'display: block;' : 'display: none;'; ?>">
                        <?php
                        wp_dropdown_categories([
                            'show_option_all' => 'Selecciona un Departamento',
                            'taxonomy'        => 'departamento',
                            'name'            => 'departamento_slug',
                            'class'           => 'ap-department-select',
                            'hierarchical'    => true,
                            'value_field'     => 'slug',
                            'hide_empty'      => 0,
                            'selected'        => $selected_department,
                        ]);
                        ?>
                    </div>
                </div>

            </form>
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const scopeRadios = document.querySelectorAll('input[name="search_scope"]');
                const deptWrapper = document.getElementById('ap-department-wrapper');
                if (!deptWrapper) return;
                const deptSelect = deptWrapper.querySelector('select[name="departamento_slug"]');

                scopeRadios.forEach(radio => {
                    radio.addEventListener('change', function() {
                        if (this.value === 'department') {
                            deptWrapper.style.display = 'block';
                        } else {
                            deptWrapper.style.display = 'none';
                            if(deptSelect) {
                                deptSelect.value = ''; 
                            }
                        }
                    });
                });
            });
        </script>
        <?php
        return ob_get_clean();
    }
    
public function filter_search_query($query) {
        if ($query->is_main_query() && !is_admin() && ($query->is_search() || $query->is_tax('departamento')) ) {
            
            if (isset($_GET['post_type']) && $_GET['post_type'] === 'aviso') {
                $query->set('post_type', 'aviso');
            } else {
                return;
            }

            $tax_query = $query->get('tax_query') ?: [];
            if (isset($_GET['search_scope']) && $_GET['search_scope'] === 'department' && isset($_GET['departamento_slug']) && !empty($_GET['departamento_slug'])) {
                $tax_query[] = [
                    'taxonomy' => 'departamento',
                    'field'    => 'slug',
                    'terms'    => sanitize_text_field($_GET['departamento_slug']),
                ];
            }
            if (!empty($tax_query)) {
                $query->set('tax_query', $tax_query);
            }

            $meta_query = $query->get('meta_query') ?: [];
            $meta_query['relation'] = 'AND';

            if (isset($_GET['ad_type']) && is_array($_GET['ad_type']) && !empty($_GET['ad_type'])) {
                $ad_type_clean = array_map('sanitize_text_field', $_GET['ad_type']);
                $meta_query[] = [
                    'key'     => 'ap_ad_type',
                    'value'   => $ad_type_clean,
                    'compare' => 'IN',
                ];
            }

            // --- ¡CORRECCIÓN! Se eliminan las comas antes de validar ---
            if (isset($_GET['min_price'])) {
                $min_price = str_replace(',', '', $_GET['min_price']);
                if (is_numeric($min_price)) {
                    $meta_query[] = [ 'key' => 'ap_price', 'value' => floatval($min_price), 'compare' => '>=', 'type' => 'NUMERIC' ];
                }
            }
            if (isset($_GET['max_price'])) {
                $max_price = str_replace(',', '', $_GET['max_price']);
                if (is_numeric($max_price) && floatval($max_price) > 0) {
                     $meta_query[] = [ 'key' => 'ap_price', 'value' => floatval($max_price), 'compare' => '<=', 'type' => 'NUMERIC' ];
                }
            }
            
            if (isset($_GET['destacado']) && $_GET['destacado'] === '1') {
                $meta_query[] = ['key' => 'ap_distintivo_1', 'compare' => 'EXISTS'];
                $meta_query[] = ['key' => 'ap_distintivo_1', 'value' => '', 'compare' => '!='];
            }

            if (isset($_GET['verificado']) && $_GET['verificado'] === '1') {
                $meta_query[] = ['key' => 'ap_distintivo_2', 'compare' => 'EXISTS'];
                $meta_query[] = ['key' => 'ap_distintivo_2', 'value' => '', 'compare' => '!='];
            }

            if (count($meta_query) > 1) {
                $query->set('meta_query', $meta_query);
            }

            $query->set('orderby', 'date');
            $query->set('order', 'DESC');
            $query->set('posts_per_page', 20);
        }
    }
}