<?php
// phpcs:disable
/**
 * The Template for displaying product archives, including the main shop page which is a post type archive
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/archive-product.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see        https://docs.woothemes.com/document/template-structure/
 * @author        WooThemes
 * @package    WooCommerce/Templates
 * @version     3.4.0
 */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

get_header('shop');


/*если поиск*/
if (is_search() && isset($_GET['post_type']) && $_GET['post_type'] === 'product') :
    if (have_posts()) : ?>
        <div class="container">
            <header class="woocommerce-products-header">
                <h1 class="woocommerce-products-header__title page-title">
                    <?php printf(__('Search Results for: %s', 'your-textdomain'), get_search_query()); ?>
                </h1>
            </header>

            <?php woocommerce_product_loop_start(); ?>

            <?php while (have_posts()) : the_post(); ?>
                <?php wc_get_template_part('content', 'product'); ?>
            <?php endwhile; ?>

            <?php woocommerce_product_loop_end(); ?>

            <?php woocommerce_pagination(); ?>
        </div>

    <?php else : ?>
        <div class="container">
            <header class="woocommerce-products-header">
                <h1 class="woocommerce-products-header__title page-title">
                    <?php printf(__('No products found for: %s', 'your-textdomain'), get_search_query()); ?>
                </h1>
            </header>

            <p><?php _e('Sorry, no products matched your search.', 'your-textdomain'); ?></p>
        </div>
<?php endif;

    return;
endif;
/*<<<<*/


global $wp_query, $woocommerce_loop;

$autima_opt = get_option('autima_opt');
$shoplayout = 'sidebar';

if (isset($autima_opt['shop_layout']) && $autima_opt['shop_layout'] != '') {
    $shoplayout = $autima_opt['shop_layout'];
}
if (isset($_GET['layout']) && $_GET['layout'] != '') {
    $shoplayout = $_GET['layout'];
}
$shopsidebar = 'left';
if (isset($autima_opt['sidebarshop_pos']) && $autima_opt['sidebarshop_pos'] != '') {
    $shopsidebar = $autima_opt['sidebarshop_pos'];
}
if (isset($_GET['sidebar']) && $_GET['sidebar'] != '') {
    $shopsidebar = $_GET['sidebar'];
}
if (!is_active_sidebar('sidebar-shop')) {
    $shoplayout = 'fullwidth';
}
$autima_shop_main_extra_class = NULl;
if ($shopsidebar == 'left') {
    $autima_shop_main_extra_class = 'order-lg-last';
}
$main_column_class = NULL;
switch ($shoplayout) {
    case 'fullwidth':
        Autima_Class::autima_shop_class('shop-fullwidth');
        $shopcolclass = 12;
        $shopsidebar = 'none';
        $productcols = 4;
        break;
    default:
        Autima_Class::autima_shop_class('shop-sidebar');
        $shopcolclass = 9;
        $productcols = 3;
        $main_column_class = 'main-column';
}
$autima_viewmode = Autima_Class::autima_show_view_mode();
?>
<div class="main-container">
    <div class="breadcrumb-container">
        <div class="container">
            <?php
            /**
             * Hook: woocommerce_before_main_content.
             *
             * @hooked woocommerce_output_content_wrapper - 10 (outputs opening divs for the content)
             * @hooked woocommerce_breadcrumb - 20
             * @hooked WC_Structured_Data::generate_website_data() - 30
             */
            do_action('woocommerce_before_main_content');
            ?>
        </div>
    </div>

    <div class="shop_content">
        <?php
        $curcat = get_queried_object(); ?>

        <div class="container">
            <h1 class="entry-title entry-title-cat"><?php echo $curcat->name; ?></h1>
        </div>

        <div class="cat-descr-top">
            <div class="container">
                <?php
                $cat_descr_top = get_field('cat_descr_top', 'product_cat_' . $curcat->term_id);

                echo $cat_descr_top;
                ?>
            </div>
        </div>


        <?php
        if (($curcat->parent == 0) || ($curcat->term_id == 1245)) {
            $prod_cat_args = array(
                'taxonomy' => 'product_cat',
                'orderby' => 'id', // здесь по какому полю сортировать
                'hide_empty' => false, // скрывать категории без товаров или нет
                'parent' => $curcat->term_id // id родительской категории
            );

            $woo_categories = get_categories($prod_cat_args);
            echo "<div class='cat_blocks_container'>";
        ?>


            <div class="kv-cat_blocks">
                <?php
                foreach ($woo_categories as $woo_cat) {
                    $woo_cat_id = $woo_cat->term_id; //category ID
                    $woo_cat_name = $woo_cat->name; //category name
                    $woo_cat_slug = $woo_cat->slug; //category slug
                    echo '<a href="' . get_term_link($woo_cat_id, 'product_cat') . '">';
                    echo '<div class="cat_blocks kv-cat_blocks__item">';
                    $category_thumbnail_id = get_term_meta($woo_cat_id, 'thumbnail_id', true);
                    $thumbnail_image_url = wp_get_attachment_url($category_thumbnail_id);
                    echo '<img src="' . $thumbnail_image_url . '"/>';
                    echo '<h2>';
                    echo $woo_cat_name;
                    echo '</h2>';
                    echo "</div>";
                    echo "</a>";
                } ?>

                <div class="kv-cat_blocks__item"></div>
                <div class="kv-cat_blocks__item"></div>
                <div class="kv-cat_blocks__item"></div>
                <div class="kv-cat_blocks__item"></div>

                <?php
                echo "</div>"; ?>
            </div>


            <!--описание у категории-->
            <div class="kv-descr-cat container">
                <?php
                $current_term = get_queried_object();

                if ($current_term && !is_wp_error($current_term)) {
                    if (!empty($current_term->description)) {
                        echo '<div class="category-description">';
                        echo wpautop(wptexturize($current_term->description));
                        echo '</div>';
                    }
                }
                ?>
            </div>

        <?php
        } else {
        ?>

            <!--панель фильтра-->
            <div class="container wrap-filter-panel">
                <div class="row">
                    <div class="filter-panel">
                        <div class="filter-panel__label">
                            Фильтр:
                        </div>

                        <div class="filter-panel__attr">
                        </div>

                        <div class="filter-panel__btn">
                            <button>Сбросить фильтр</button>
                        </div>
                    </div>
                </div>
            </div>

            <!--дочерняя категория-->
            <div class="container shop_content-inner">
                <!--панель сортировки-->
                <div class="row">
                    <div class="container wrap-sorting-panel">
                        <select name="sorting-attrib" id="sorting-attrib">
                        </select>
                    </div>
                </div>

                <!--таблица товаров-->
                <div class="row">
                    <div id="archive-product"
                        class="page-content col-12 <?php echo 'col-lg-' . $shopcolclass; ?> <?php echo esc_attr($autima_viewmode); ?> <?php echo esc_attr($main_column_class); ?> <?php echo esc_attr($autima_shop_main_extra_class); ?>">

                        <div class="shop-products products list-view <?php echo esc_attr($shoplayout); ?>">
                            <div class="shop-products-inner">
                                <!--панель свойств атрибута-->
                                <div id="attribute-filter-panel" class="attribute-filter-panel" style="display: none;"></div>

                                <div style="overflow-y:auto;min-width:100%">
                                    <?php
                                    $term = get_queried_object();       // Текущая категория
                                    $category_id = $term->term_id;

                                    // ACF-метки для нестандартных атрибутов
                                    $custom_labels = [
                                        'pa_razmer-1' => get_field('razmer_1', 'product_cat_' . $category_id),
                                        'pa_razmer-2' => get_field('razmer_2', 'product_cat_' . $category_id),
                                    ];

                                    // Флаги отображения атрибутов
                                    $visible_attributes = [
                                        'pa_marka' => get_field('show_marka', 'product_cat_' . $category_id),
                                        'pa_razmer-1' => get_field('show_razmer_1', 'product_cat_' . $category_id),
                                        'pa_razmer-2' => get_field('show_razmer_2', 'product_cat_' . $category_id),
                                        'pa_tolshina' => get_field('show_tolshina', 'product_cat_' . $category_id),
                                        'pa_dlina' => get_field('show_dlina', 'product_cat_' . $category_id),
                                        'pa_ves' => get_field('show_ves', 'product_cat_' . $category_id),
                                    ];

                                    // --- [OVERRIDE] Особые категории: Лист, Плита, Полоса, Шина, Лента ---
                                    $cat_name = isset($term->name) ? (string)$term->name : '';
                                    $is_special_category = preg_match('/(?<!\S)(Лист|Плита|Полоса|Шина|Лента)(?!\S)/u', $cat_name);
                                    $is_lenta_category   = preg_match('/(?<!\S)Лента(?!\S)/u', $cat_name);

                                    if ($is_special_category) {
                                        // Жёстко задаём видимость и порядок колонок (игнорируем ACF)
                                        $visible_attributes = [
                                            'pa_marka'     => true,
                                            'pa_tolshina'  => true,
                                            'pa_razmer-1'  => true,
                                            // 'pa_dlina' добавляем только если это НЕ "Лента"
                                        ];
                                        if (!$is_lenta_category) {
                                            $visible_attributes['pa_dlina'] = true;
                                        }
                                        $visible_attributes['pa_ves'] = true;
                                    }
                                    // --- [/OVERRIDE] ---


                                    // Принудительный порядок/набор колонок для категорий, чьё имя начинается со слова "Лист"
                                    if (isset($term->name)) {
                                        $cat_name = $term->name;

                                        $starts_with_list = (mb_strpos($cat_name, 'Лист') === 0) &&
                                            (mb_strlen($cat_name, 'UTF-8') === 4 || mb_substr($cat_name, 4, 1, 'UTF-8') === ' ');
                                        $starts_with_plita = (mb_strpos($cat_name, 'Плита') === 0) &&
                                            (mb_strlen($cat_name, 'UTF-8') === 5 || mb_substr($cat_name, 5, 1, 'UTF-8') === ' ');

                                        if ($starts_with_list || $starts_with_plita) {
                                            $visible_attributes = [
                                                'pa_marka' => true,
                                                'pa_tolshina' => true,
                                                'pa_razmer-1' => true,
                                                'pa_dlina' => true,
                                                'pa_ves' => true,
                                            ];
                                        }
                                    }

                                    echo '<table class="woocommerce-products-table">';
                                    echo '<thead><tr>';
                                    echo '<th>Продукция</th>';

                                    // Заголовки атрибутов
                                    foreach ($visible_attributes as $attr => $show) {
                                        if ($show) {
                                            $label = isset($custom_labels[$attr]) ? $custom_labels[$attr] : wc_attribute_label($attr);

                                            // Принудительная метка для ширины в особых категориях
                                            if ($is_special_category && $attr === 'pa_razmer-1') {
                                                $label = 'Ширина';
                                            }

                                            // Единицы измерения
                                            $unit = '';

                                            if (in_array($attr, ['pa_razmer-1', 'pa_razmer-2', 'pa_tolshina', 'pa_dlina'])) {
                                                $unit = '<div>мм.</div>';
                                            } elseif ($attr === 'pa_ves') {
                                                $unit = '<div>гр.</div>';
                                            }

                                            // Слаг и классы
                                            $slug  = str_replace('pa_', '', $attr);
                                            $class = 'attr-col attr-col--' . esc_attr($slug);

                                            // Список атрибутов, которые должны быть ссылками
                                            $clickable_attrs = ['pa_marka', 'pa_razmer-1', 'pa_razmer-2', 'pa_tolshina', 'pa_dlina'];

                                            // Генерация HTML заголовка
                                            if (in_array($attr, $clickable_attrs)) {
                                                $label_html = '<a href="#" class="filter-toggle" data-attribute="' . esc_attr($slug) . '">' . esc_html($label) . ' ' . $unit . '</a>';
                                            } else {
                                                $label_html = esc_html($label) . ' ' . $unit;
                                            }

                                            echo '<th class="' . $class . '">' . $label_html . '</th>';
                                        }
                                    }

                                    echo '<th style="line-height: 100%;">Цена<div style="font-weight: 300;">(руб/шт)</div></th>';
                                    echo '<th style="line-height: 100%;">Цена<div style="font-weight: 300;">(руб/кг)</div></th>';
                                    echo '<th>Количество</th>';
                                    echo '<th>Купить</th>';
                                    echo '</tr></thead><tbody>';


                                    // Получаем товары категории (до 30 штук)
                                    /*                                    $args = [
                                        'post_type' => 'product',
                                        'posts_per_page' => 30,
                                        'tax_query' => [[
                                            'taxonomy' => 'product_cat',
                                            'field'    => 'term_id',
                                            'terms'    => $category_id,
                                        ]],
                                        'post_status' => 'publish',
                                    ];

                                    $products = new WP_Query($args);*/

                                    if (have_posts()) :
                                        while (have_posts()) : the_post();
                                            global $product;
                                            echo '<tr>';

                                            echo '<td>';
                                            // Добавляем миниатюру товара
                                            echo '<div class="product-thumbnail">';
                                            echo get_the_post_thumbnail($product->get_id(), 'thumbnail');
                                            echo '</div>';
                                            // Существующая ссылка с названием
                                            echo '<a href="' . get_permalink() . '">' . get_the_title() . '</a>';
                                            echo '</td>';

                                            // Атрибуты
                                            foreach ($visible_attributes as $attr => $show) {
                                                if ($show) {
                                                    $values = wc_get_product_terms($product->get_id(), $attr, ['fields' => 'names']);

                                                    echo '<td>' . esc_html(implode(', ', $values)) . '</td>';
                                                }
                                            }

                                            // Цены из вариаций
                                            $price_sht = $price_kg = '-';
                                            $available_units = [];

                                            if ($product->is_type('variable')) {
                                                $variable_product = new WC_Product_Variable($product->get_id());
                                                $variations = $variable_product->get_available_variations();

                                                foreach ($variations as $variation) {
                                                    $unit = isset($variation['attributes']['attribute_pa_sht-kg']) ? trim($variation['attributes']['attribute_pa_sht-kg']) : '';
                                                    $unit = urldecode($unit);

                                                    $price = isset($variation['display_price']) ? $variation['display_price'] : '';

                                                    if ($unit === '' || $price === '') continue;

                                                    $available_units[$unit] = $variation['variation_id'];

                                                    if ($unit === 'шт' && $price_sht === '-') {
                                                        $price_sht = wc_price($price);
                                                    }

                                                    if ($unit === 'кг' && $price_kg === '-') {
                                                        $price_kg = wc_price($price);
                                                    }
                                                }
                                            }

                                            echo '<td>' . $price_sht . '</td>';
                                            echo '<td>' . $price_kg . '</td>';

                                            // Кол-во и селект
                                            echo '<td class="selected-count-prod">
                                            <input type="number" name="quantity" min="1" value="1" class="qty" />
                                         
                                            <select name="unit" class="unit-selector">';
                                            foreach ($available_units as $unit => $_variation_id) {
                                                echo '<option value="' . htmlspecialchars($unit, ENT_QUOTES, 'UTF-8') . '">' . esc_html($unit) . '</option>';
                                            }
                                            echo '</select></td>';

                                            // Кнопка "Купить"
                                            $data_json = htmlspecialchars(json_encode($available_units, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');

                                            echo '<td>
                                                <button class="add-to-cart-btn"
                                                    data-product-id="' . (int) $product->get_id() . '"
                                                    data-variations=\'' . $data_json . '\'>
                                                    <i class="fa fa-shopping-cart" aria-hidden="true"></i>
                                                </button>
                                            </td>';

                                            echo '</tr>';
                                        endwhile;

                                        wp_reset_postdata();
                                    else :
                                        echo '<tr><td colspan="99">Нет товаров в категории.</td></tr>';
                                    endif;

                                    echo '</tbody></table>'; ?>

                                    <div class="load-more-wrapper" style="text-align:center; margin-top:20px;">
                                        <button id="load-more-products" class="btn btn-primary"
                                            data-page="1"
                                            data-category="<?php echo esc_attr($category_id); ?>">
                                            Загрузить ещё
                                        </button>
                                    </div>
                                </div><!-- .table-responsive -->
                            </div><!-- .col-12 -->
                        </div><!-- .row -->
                    </div><!-- .container -->
                </div><!-- .shop-products -->
            </div><!-- .main-container -->


            <!-- произвольное поле категории -->
            <div class="cat-descr-bottom">
                <div class="container">
                    <?php
                    $cat_descr_bottom = get_field('cat_descr_bottom', 'product_cat_' . $curcat->term_id);

                    echo $cat_descr_bottom;
                    ?>
                </div>
            </div>

            <!-- список ссылок на загрузку документов -->
            <div class="links-downloads">
                <div class="container">
                    <?php
                    get_template_part('inc/links-docs', null, ['category_id' => $category_id]);
                    ?>
                </div>
            </div>
    </div><!-- .page-content -->
<?php
        } ?>

</div><!-- .main -->
</div><!-- .page-wrapper -->

<?php get_footer('shop'); ?>