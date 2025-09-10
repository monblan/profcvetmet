<?php
// phpcs:disable

if (!defined('_S_VERSION')) {
    // Replace the version number of the theme on each release.
    define('_S_VERSION', '1.0.0');
}

function autima_child_enqueue_styles()
{
    wp_enqueue_style('autima-kv-style', get_template_directory_uri() . '/style.css');

    wp_enqueue_script('inputmask-scripts', get_stylesheet_directory_uri() . '/libs/jquery.inputmask.min.js', array('jquery'), '', true);

    wp_enqueue_script('fancybox-scripts', get_stylesheet_directory_uri() . '/libs/fancybox/jquery.fancybox.js', array('jquery'), '', true);
    wp_enqueue_style('fancybox-style', get_stylesheet_directory_uri() . '/libs/fancybox/jquery.fancybox.css', array(), _S_VERSION);

    wp_enqueue_style('font-styles', get_stylesheet_directory_uri() . '/fonts/stylesheet.css', array(), _S_VERSION);
    wp_enqueue_style('kv-styles', get_stylesheet_directory_uri() . '/css/kv-styles.css', array(), _S_VERSION);
    wp_enqueue_style('kv-media', get_stylesheet_directory_uri() . '/css/kv-media.css', array(), _S_VERSION);

    wp_enqueue_script('kv-scripts', get_stylesheet_directory_uri() . '/js/kv-scripts.js', array('jquery'), '1.0.7', true);
    
    // Локализация AJAX URL для корзины
    wp_localize_script('kv-scripts', 'kv_ajax_params', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('kv_ajax_nonce')
    ));
}

add_action('wp_enqueue_scripts', 'autima_child_enqueue_styles');

if (function_exists('acf_add_options_page')) {
    acf_add_options_page();
}


/*шорткод продукции для Главной страниц*/
add_shortcode('acf_menu_blocks', 'render_acf_menu_blocks');

function render_acf_menu_blocks()
{
    if (!function_exists('get_field') || !have_rows('types_products')) {
        return '<p>Данные отсутствуют или плагин ACF не активирован.</p>';
    }

    $output = '<section class="popular-section">';
    $output .= '<div class="acf-menu-blocks">';

    // Перебираем повторяющееся поле
    while (have_rows('types_products')) {
        the_row();

        $name_menu = get_sub_field('name_menu'); // Название меню
        $img = get_sub_field('img'); // Фоновое изображение

        // Получаем пункты меню по названию
        $menu_items = wp_get_nav_menu_items($name_menu);

        // Если меню с таким названием не существует, пропускаем итерацию
        if (!$menu_items) {
            continue;
        }

        // Генерируем HTML для одного блока
        $output .= '<div class="acf-menu-block" style="background-image: url(' . esc_url($img) . ')">';
        /*$output .= '<h3>' . esc_html($name_menu) . '</h3>';*/
        $output .= '<h3><a href="/category/' . mb_strtolower($name_menu) . '">' . esc_html($name_menu) . '</a></h3>';

        // Формируем список пунктов меню
        $output .= '<ul>';
        foreach ($menu_items as $item) {
            $output .= '<li><a href="' . esc_url($item->url) . '">' . esc_html($item->title) . '</a></li>';
        }

        $output .= '</ul>';

        $output .= '</div>'; // Закрываем блок
    }

    $output .= '</div>';
    $output .= '<div class="popular-section__descr">';
    $output .= get_field('advantages');
    $output .= '<div class="popular-section__btns">';
    $output .= '<p>Нет времени искать? Наш менеджер подберет необходимые товары по телефону</p>';
    $output .= '<a class="fancybox" href="#hideBlockGetInfo" data-fancybox="">Заказать обратный звонок</a>';
    $output .= '</div>';
    $output .= '</div>';
    $output .= '</section>';

    return $output;
}


class Custom_Walker_Nav_Menu extends Walker_Nav_Menu
{
    // Вывод родительского элемента
    public function start_el(&$output, $item, $depth = 0, $args = null, $id = 0)
    {
        $class_names = join(' ', apply_filters('nav_menu_css_class', array_filter($item->classes), $item, $args));
        $class_names = $class_names ? ' class="' . esc_attr($class_names) . '"' : '';

        $output .= ($depth === 0) ? '<div' . $class_names . '>' : '';

        $atts = array();
        $atts['href'] = !empty($item->url) ? $item->url : '';
        $atts['class'] = 'menu-link';

        $attributes = '';
        foreach ($atts as $attr => $value) {
            if (!empty($value)) {
                $value = esc_attr($value);
                $attributes .= ' ' . $attr . '="' . $value . '"';
            }
        }

        $item_output = sprintf(
            '<a%s>%s</a>',
            $attributes,
            apply_filters('the_title', $item->title, $item->ID)
        );

        $output .= ($depth === 0) ? $item_output : '<li>' . $item_output;
    }

    // Завершение вывода родительского элемента
    public function end_el(&$output, $item, $depth = 0, $args = null)
    {
        $output .= ($depth === 0) ? '</div>' : '</li>';
    }

    // Начало списка для дочерних элементов
    public function start_lvl(&$output, $depth = 0, $args = null)
    {
        if ($depth === 0) {
            $output .= '<ul class="submenu">';
        }
    }

    // Завершение списка для дочерних элементов
    public function end_lvl(&$output, $depth = 0, $args = null)
    {
        if ($depth === 0) {
            $output .= '</ul>';
        }
    }
}


add_action('wp_ajax_custom_add_to_cart', 'custom_add_to_cart');
add_action('wp_ajax_nopriv_custom_add_to_cart', 'custom_add_to_cart');

function custom_add_to_cart()
{
    $product_id = intval($_POST['product_id']);
    $qty = intval($_POST['qty']);
    $variation_id = intval($_POST['variation_id']);

    if (!$product_id || !$variation_id || $qty < 1) {
        wp_send_json_error('Неверные данные');
    }

    $added = WC()->cart->add_to_cart($product_id, $qty, $variation_id);

    if ($added) {
        // Получим данные для кастомного счетчика
        $cart_qty = WC()->cart->get_cart_contents_count();
        $cart_total = WC()->cart->get_cart_total(); // отдает <span class="woocommerce-Price-amount">...</span>

        WC_AJAX::get_refreshed_fragments(); // отправляет готовый JSON с HTML-фрагментами

        exit;
    } else {
        wp_send_json_error('Ошибка при добавлении');
    }
}


add_action('wp_ajax_load_more_products', 'ajax_load_more_products');
add_action('wp_ajax_nopriv_load_more_products', 'ajax_load_more_products');

function ajax_load_more_products()
{
    $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
    $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
    $filters = isset($_POST['filters']) ? $_POST['filters'] : [];
    $filter_attributes = ['marka', 'razmer-1', 'razmer-2', 'tolshina', 'dlina'];

    $orderby_custom = isset($_POST['orderby_custom']) ? sanitize_text_field($_POST['orderby_custom']) : '';

    if (!$category_id) {
        wp_send_json_error('Нет категории');
    }

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

    // Принудительный порядок/набор колонок для категорий, чьё имя начинается со слова "Лист"
    // Принудительный порядок/набор колонок для особых категорий:
    // Лист, Плита, Полоса, Шина, Лента (для "Лента" — без "Длина")
    $term_obj = get_term($category_id, 'product_cat');
    
    if ($term_obj && !is_wp_error($term_obj) && isset($term_obj->name)) {
        $cat_name = (string)$term_obj->name;

        // Ищем ключевое слово как отдельное слово в любом месте названия
        $is_special_category = preg_match('/(?<!\S)(Лист|Плита|Полоса|Шина|Лента)(?!\S)/u', $cat_name);
        $is_lenta_category   = preg_match('/(?<!\S)Лента(?!\S)/u', $cat_name);

        if ($is_special_category) {
            // Жёсткий набор и порядок колонок (игнорируем ACF)
            $visible_attributes = [
                'pa_marka'     => true,
                'pa_tolshina'  => true,
                'pa_razmer-1'  => true, // "Ширина" — заголовок задаётся в шаблоне
                // 'pa_dlina' добавляем только если это НЕ "Лента"
            ];
            if (!$is_lenta_category) {
                $visible_attributes['pa_dlina'] = true;
            }
            $visible_attributes['pa_ves'] = true;
        }
    }


    // --- Новый массив для проверки видимости по ключу метаполя ---
    $visible_meta = [
        '_razmer_1' => get_field('show_razmer_1', 'product_cat_' . $category_id),
        '_razmer_2' => get_field('show_razmer_2', 'product_cat_' . $category_id),
        '_tolshina' => get_field('show_tolshina', 'product_cat_' . $category_id),
    ];

    // Категория — ОБЯЗАТЕЛЬНО в любом случае
    $tax_query = [
        'relation' => 'AND', // Добавляем сюда!
        [
            'taxonomy' => 'product_cat',
            'field' => 'term_id',
            'terms' => [$category_id],
        ]
    ];

    // Добавляем фильтры
    foreach ($filter_attributes as $slug) {
        if (!empty($filters[$slug]) && is_array($filters[$slug])) {
            $tax_query[] = [
                'taxonomy' => 'pa_' . $slug,
                'field' => 'name',
                'terms' => array_map('sanitize_text_field', $filters[$slug]),
                'operator' => 'IN',
            ];
        }
    }

    $args = [
        'post_type' => 'product',
        'posts_per_page' => 20,
        'paged' => $page,
        'post_status' => 'publish',
        'tax_query' => $tax_query,
    ];

    /*сортировка*/
    if (!empty($orderby_custom)) {
        $orderby_parts = explode('_', $orderby_custom);

        if (count($orderby_parts) === 3) {
            $orderby_field = $orderby_parts[0] . '_' . $orderby_parts[1];
            $orderby_dir = $orderby_parts[2];
        } elseif (count($orderby_parts) === 2) {
            $orderby_field = $orderby_parts[0];
            $orderby_dir = $orderby_parts[1];
        }

        $meta_key = '';

        switch ($orderby_field) {
            case 'price_sht':
                $meta_key = '_price_sht';
                break;
            case 'price_kg':
                $meta_key = '_price_kg';
                break;
            case 'ves':
                $meta_key = '_ves';
                break;
            case 'marka':
                $meta_key = '_marka_name';
                break;
            case 'dlina':
                $meta_key = '_dlina';
                break;
            case 'tolshina':
                $meta_key = '_tolshina';
                break;
            case 'razmer-1':
                $meta_key = '_razmer_1';
                break;
            case 'razmer-2':
                $meta_key = '_razmer_2';
                break;
        }

        if ($meta_key) {
            $args['meta_key'] = $meta_key;
            $args['orderby'] = in_array($orderby_field, ['marka']) ? 'meta_value' : 'meta_value_num';
            $args['order'] = strtoupper($orderby_dir);
        }
    } else {
        // --- Сортировка по умолчанию через ACF ---
        $sort_default = get_field('sort_default_category', 'product_cat_' . $category_id);

        if ($sort_default && $sort_default !== 'default' && !empty($visible_meta[$sort_default])) {
            $args['meta_key'] = $sort_default;
            $args['orderby'] = 'meta_value_num';
            $args['order'] = 'ASC';
            $args['meta_query'] = isset($args['meta_query']) ? $args['meta_query'] : [];
            $args['meta_query'][] = [
                'key' => $sort_default,
                'compare' => 'EXISTS'
            ];
        } else {
            $args['orderby'] = 'menu_order title';
            $args['order'] = 'ASC';
        }
    }

    $products = new WP_Query($args);

    ob_start();

    if ($products->have_posts()) :
        while ($products->have_posts()) : $products->the_post();
            global $product;

            echo '<tr>';

            echo '<td>';
            echo '<div class="product-thumbnail">';
            echo get_the_post_thumbnail($product->get_id(), 'thumbnail');
            echo '</div>';
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

            // Количество и селектор
            echo '<td class="selected-count-prod">
                <input type="number" name="quantity" min="1" value="1" class="qty" style="width:60px" />
                <select name="unit" class="unit-selector">';

            foreach ($available_units as $unit => $_variation_id) {
                echo '<option value="' . htmlspecialchars($unit, ENT_QUOTES, 'UTF-8') . '">' . esc_html($unit) . '</option>';
            }
            echo '</select></td>';

            // Кнопка "В корзину"
            $data_json = htmlspecialchars(json_encode($available_units, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');

            echo '<td>
                <button class="add-to-cart-btn"
                    data-product-id="' . (int)$product->get_id() . '"
                    data-variations=\'' . $data_json . '\'>
                    <i class="fa fa-shopping-cart" aria-hidden="true"></i>
                </button>
            </td>';

            echo '</tr>';
        endwhile;

        wp_reset_postdata();
    endif;

    $html = ob_get_clean();

    if (!empty($html)) {
        wp_send_json_success($html);
    } else {
        wp_send_json_error('Больше товаров нет');
    }
}


// Предзагрузка терминов по нужным атрибутам для таблицы товаров
add_action('wp_enqueue_scripts', function () {
    // Только на страницах категорий товаров
    if (!is_product_category()) {
        return;
    }

    $term = get_queried_object();

    if (!$term || !isset($term->term_id)) {
        return;
    }

    $category_id = $term->term_id;

    // Атрибуты, для которых делаем фильтрацию
    $filter_attributes = ['pa_marka', 'pa_razmer-1', 'pa_razmer-2', 'pa_tolshina', 'pa_dlina'];
    $attribute_terms = [];

    // Получаем все товары этой категории
    $product_ids = get_posts([
        'post_type' => 'product',
        'posts_per_page' => -1,
        'fields' => 'ids',
        'tax_query' => [[
            'taxonomy' => 'product_cat',
            'field' => 'term_id',
            'terms' => $category_id,
        ]],
    ]);

    // Если товары есть — собираем значения терминов по каждому атрибуту
    if (!empty($product_ids)) {
        foreach ($filter_attributes as $attr) {
            $terms = wp_get_object_terms($product_ids, $attr, ['fields' => 'names']);
            $slug = str_replace('pa_', '', $attr);
            $attribute_terms[$slug] = array_values(array_unique($terms));
        }
    }

    // Локализация для JS
    wp_localize_script('kv-scripts', 'attributeTerms', $attribute_terms);
});


/*для панели фильтра переименование атрибутов Размер 1 и Размер 2*/
add_action('wp_enqueue_scripts', function () {
    if (is_product_category()) {
        $term = get_queried_object();
        $category_id = $term->term_id;

        $acf_labels = [
            'razmer-1' => get_field('razmer_1', 'product_cat_' . $category_id),
            'razmer-2' => get_field('razmer_2', 'product_cat_' . $category_id),
        ];

        wp_localize_script('kv-scripts', 'acfLabels', $acf_labels);
    }
});


/*отвечает за основной цикл*/
add_action('pre_get_posts', function ($query) {
    if (!is_admin() && $query->is_main_query() && is_product_category()) {
        $filter_attributes = ['marka', 'razmer-1', 'razmer-2', 'tolshina', 'dlina'];
        $tax_query = [];

        // Фильтрация
        foreach ($filter_attributes as $slug) {
            if (!empty($_GET[$slug]) && is_array($_GET[$slug])) {
                $tax_query[] = [
                    'taxonomy' => 'pa_' . $slug,
                    'field' => 'name',
                    'terms' => array_map('sanitize_text_field', $_GET[$slug]),
                    'operator' => 'IN',
                ];
            }
        }

        if (!empty($tax_query)) {
            $tax_query['relation'] = 'AND';

            $query->set('tax_query', $tax_query);
        }

        // Сортировка с фронта
        if (isset($_GET['orderby_custom']) && !empty($_GET['orderby_custom'])) {
            $orderby_custom = sanitize_text_field($_GET['orderby_custom']);
            $orderby_parts = explode('_', $orderby_custom);

            if (count($orderby_parts) === 3) {
                $orderby_field = $orderby_parts[0] . '_' . $orderby_parts[1];
                $orderby_dir = $orderby_parts[2];
            } elseif (count($orderby_parts) === 2) {
                $orderby_field = $orderby_parts[0];
                $orderby_dir = $orderby_parts[1];
            }

            $meta_key = '';

            switch ($orderby_field) {
                case 'marka':
                    $meta_key = '_marka_name';
                    $orderby_type = 'meta_value'; // Строка
                    break;
                case 'price_sht':
                case 'price_kg':
                case 'ves':
                case 'dlina':
                case 'tolshina':
                case 'razmer-1':
                case 'razmer-2':
                    $meta_key = '_' . str_replace('-', '_', $orderby_field);
                    $orderby_type = 'meta_value_num'; // Число
                    break;
            }


            if (!empty($meta_key)) {
                $query->set('meta_key', $meta_key);
                $query->set('orderby', $orderby_type);
                $query->set('order', strtoupper($orderby_dir));

                // ✨ Дополнительно — сортировать только записи с этим мета-ключом!
                $meta_query = (array)$query->get('meta_query');
                $meta_query[] = [
                    'key' => $meta_key,
                    'compare' => 'EXISTS'
                ];

                $query->set('meta_query', $meta_query);
            }
        } else {
            // --- Сортировка по умолчанию через ACF ---
            $term = get_queried_object();
            if ($term && isset($term->term_id)) {
                $category_id = $term->term_id;
                $sort_default = get_field('sort_default_category', 'product_cat_' . $category_id);
                // Массив видимости атрибутов
                $visible_attributes = [
                    '_razmer_1' => get_field('show_razmer_1', 'product_cat_' . $category_id),
                    '_razmer_2' => get_field('show_razmer_2', 'product_cat_' . $category_id),
                    '_tolshina' => get_field('show_tolshina', 'product_cat_' . $category_id),
                ];
                if ($sort_default && $sort_default !== 'default' && !empty($visible_attributes[$sort_default])) {
                    $query->set('meta_key', $sort_default);
                    $query->set('orderby', 'meta_value_num');
                    $query->set('order', 'ASC');
                    $meta_query = (array)$query->get('meta_query');
                    $meta_query[] = [
                        'key' => $sort_default,
                        'compare' => 'EXISTS'
                    ];
                    $query->set('meta_query', $meta_query);
                } else {
                    // Стандартная сортировка
                    $query->set('orderby', 'menu_order title');
                    $query->set('order', 'ASC');
                }
            } else {
                // Стандартная сортировка
                $query->set('orderby', 'menu_order title');
                $query->set('order', 'ASC');
            }
        }

        // Установить лимит постов
        $query->set('posts_per_page', 20);
    }
});


/*для сортировки*/
add_action('wp_enqueue_scripts', function () {
    if (is_product_category()) {
        $term = get_queried_object();
        $category_id = $term->term_id;

        // --- [OVERRIDE] Особые категории для сортировки ---
        $cat_name = isset($term->name) ? (string)$term->name : '';
        $is_special_category = preg_match('/(?<!\S)(Лист|Плита|Полоса|Шина|Лента)(?!\S)/u', $cat_name);
        $is_lenta_category   = preg_match('/(?<!\S)Лента(?!\S)/u', $cat_name);
        // --- [/OVERRIDE] ---


        // Получаем ACF метки
        $acf_labels = [
            'razmer-1' => get_field('razmer_1', 'product_cat_' . $category_id),
            'razmer-2' => get_field('razmer_2', 'product_cat_' . $category_id),
        ];

        // Получаем видимость атрибутов
        $visible_attributes = [
            'marka' => get_field('show_marka', 'product_cat_' . $category_id),
            'razmer-1' => get_field('show_razmer_1', 'product_cat_' . $category_id),
            'razmer-2' => get_field('show_razmer_2', 'product_cat_' . $category_id),
            'dlina' => get_field('show_dlina', 'product_cat_' . $category_id),
            'tolshina' => get_field('show_tolshina', 'product_cat_' . $category_id),
            'ves' => get_field('show_ves', 'product_cat_' . $category_id),
        ];

        // Строим массив сортируемых атрибутов
        // Определяем, является ли категория "Лист" или "Плита"
        global $wp_query;

        $first_post_title = '';

        if (isset($wp_query->posts) && ! empty($wp_query->posts)) {
            $first_post = $wp_query->posts[0];

            if (is_object($first_post) && isset($first_post->ID)) {
                $first_post_title = get_the_title($first_post->ID);
            }
        }

        $is_list_or_plita = $first_post_title && preg_match('/^(Лист|Плита)(\s|$)/u', $first_post_title);

        if ($is_list_or_plita) {
            // Жёстко заданный набор атрибутов для этих категорий
            $sorting_attributes = [
                'marka'     => 'Марка',
                'tolshina'  => 'Толщина',
                'razmer-1'  => 'Ширина',
                'dlina'     => 'Длина',
                'ves'       => 'Вес',
                'price_sht' => 'Цена (руб/шт)',
                'price_kg'  => 'Цена (руб/кг)',
            ];
        } else {
            // Строим массив сортируемых атрибутов
            $sorting_attributes = [];

            if ($is_special_category) {
                // Жёсткий список, как в таблице
                $sorting_attributes = [
                    'marka'     => 'Марка',
                    'tolshina'  => 'Толщина',
                    'razmer-1'  => 'Ширина',
                    // 'dlina' добавляем только если это НЕ "Лента"
                ];
                if (!$is_lenta_category) {
                    $sorting_attributes['dlina'] = 'Длина';
                }
                $sorting_attributes['ves']       = 'Вес';
                $sorting_attributes['price_sht'] = 'Цена (руб/шт)';
                $sorting_attributes['price_kg']  = 'Цена (руб/кг)';
            } else {
                // Обычная логика: по ACF-флажкам
                foreach ($visible_attributes as $slug => $show) {
                    if ($show) {
                        switch ($slug) {
                            case 'marka':
                                $sorting_attributes[$slug] = 'Марка';
                                break;
                            case 'razmer-1':
                                // метку берём из ACF при наличии
                                $sorting_attributes[$slug] = !empty($acf_labels[$slug]) ? $acf_labels[$slug] : 'Razmer 1';
                                break;
                            case 'razmer-2':
                                $sorting_attributes[$slug] = !empty($acf_labels[$slug]) ? $acf_labels[$slug] : 'Razmer 2';
                                break;
                            case 'dlina':
                                $sorting_attributes[$slug] = 'Длина';
                                break;
                            case 'ves':
                                $sorting_attributes[$slug] = 'Вес';
                                break;
                            case 'tolshina':
                                $sorting_attributes[$slug] = 'Толщина';
                                break;
                            default:
                                $sorting_attributes[$slug] = ucfirst(str_replace('-', ' ', $slug));
                                break;
                        }
                    }
                }
                // Цены — всегда
                $sorting_attributes['price_sht'] = 'Цена (руб/шт)';
                $sorting_attributes['price_kg']  = 'Цена (руб/кг)';
            }
        }

        // Добавляем цены в любом случае
        $sorting_attributes['price_sht'] = 'Цена (руб/шт)';
        $sorting_attributes['price_kg'] = 'Цена (руб/кг)';

        wp_localize_script('kv-scripts', 'sortingAttributes', $sorting_attributes);
    }
});


/*
разово, добавляет метаполя товарам. Метаполя нужны для сортировки
Выполнение:
    site/wp-admin/?run_update_meta=1&offset=0
    Далее — по кнопке "Продолжить", она появится сама.
*/
add_action('admin_init', function () {
    if (!current_user_can('manage_options') || !isset($_GET['run_update_meta'])) {
        return;
    }

    set_time_limit(0);
    ini_set('memory_limit', '512M');

    $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
    $limit = 500;

    $args = [
        'post_type' => 'product',
        'posts_per_page' => $limit,
        'offset' => $offset,
        'post_status' => 'publish',
        'fields' => 'ids',
        'orderby' => 'ID',
        'order' => 'ASC',
    ];

    $products = get_posts($args);
    $updated_count = 0;

    foreach ($products as $product_id) {
        $product = wc_get_product($product_id);
        if (!$product) continue;

        // Цены из вариаций
        if ($product->is_type('variable')) {
            $children = $product->get_children();
            $price_sht = $price_kg = null;

            foreach ($children as $variation_id) {
                $variation = wc_get_product($variation_id);
                if (!$variation) continue;

                $sht_kg = mb_strtolower($variation->get_attribute('pa_sht-kg'), 'UTF-8');
                $price = $variation->get_price();

                if (($sht_kg === 'шт' || $sht_kg === 'шт.') && is_numeric($price)) {
                    $price_sht = $price;
                } elseif (($sht_kg === 'кг' || $sht_kg === 'кг.') && is_numeric($price)) {
                    $price_kg = $price;
                }
            }

            if ($price_sht !== null) update_post_meta($product_id, '_price_sht', $price_sht);
            if ($price_kg !== null) update_post_meta($product_id, '_price_kg', $price_kg);
        }

        // Атрибуты → мета
        $map = [
            'pa_marka' => '_marka_name',
            'pa_razmer-1' => '_razmer_1',
            'pa_razmer-2' => '_razmer_2',
            'pa_tolshina' => '_tolshina',
            'pa_dlina' => '_dlina',
            'pa_ves' => '_ves',
        ];

        foreach ($map as $tax => $meta_key) {
            $val = $product->get_attribute($tax);

            if (!$val) continue;

            if ($tax !== 'pa_marka') {
                $val = preg_replace('/[^0-9.]/', '', $val);
            }

            if ($val !== '') {
                update_post_meta($product_id, $meta_key, $val);
            }
        }

        $updated_count++;
    }

    echo "<div class='notice notice-success'><p>Обновлено товаров: $updated_count (offset: $offset)</p></div>";

    // ✅ Ссылка на следующий батч
    if (count($products) === $limit) {
        $next = admin_url('admin.php?run_update_meta=1&offset=' . ($offset + $limit));
        echo "<p><a href='$next' class='button'>Продолжить со следующей порцией</a></p>";
    } else {
        echo "<p><strong>Все товары обработаны.</strong></p>";
    }
});


/*автосохранение метаполей товара*/
add_action('woocommerce_update_product', function ($product_id) {
    $product = wc_get_product($product_id);

    if (!$product || $product->get_status() !== 'publish') {
        return;
    }

    // Работа с вариациями
    if ($product->is_type('variable')) {
        $variations = $product->get_children();
        $price_sht = null;
        $price_kg = null;

        foreach ($variations as $variation_id) {
            $variation = wc_get_product($variation_id);
            if (!$variation) {
                continue;
            }

            $sht_kg = $variation->get_attribute('pa_sht-kg');
            $price = $variation->get_price();

            if (mb_strtolower($sht_kg, 'UTF-8') === 'шт.' || mb_strtolower($sht_kg, 'UTF-8') === 'шт') {
                if (is_numeric($price)) {
                    $price_sht = $price;
                }
            }

            if (mb_strtolower($sht_kg, 'UTF-8') === 'кг.' || mb_strtolower($sht_kg, 'UTF-8') === 'кг') {
                if (is_numeric($price)) {
                    $price_kg = $price;
                }
            }
        }

        if (is_numeric($price_sht)) {
            update_post_meta($product_id, '_price_sht', $price_sht);
        }

        if (is_numeric($price_kg)) {
            update_post_meta($product_id, '_price_kg', $price_kg);
        }
    }

    // Работа с атрибутами
    $attributes_to_meta = [
        'pa_marka' => '_marka_name',
        'pa_razmer-1' => '_razmer_1',
        'pa_razmer-2' => '_razmer_2',
        'pa_tolshina' => '_tolshina',
        'pa_dlina' => '_dlina',
        'pa_ves' => '_ves',
    ];

    foreach ($attributes_to_meta as $attribute_slug => $meta_key) {
        $value = $product->get_attribute($attribute_slug);

        if (!empty($value)) {
            // Для числовых атрибутов чистим
            if ($attribute_slug !== 'pa_marka') {
                $value = preg_replace('/[^0-9.]/', '', $value);
            }

            if (!empty($value)) {
                update_post_meta($product_id, $meta_key, $value);
            }
        }
    }
});


add_action('wp_ajax_update_cart_item_quantity', 'ajax_update_cart_item_quantity');
add_action('wp_ajax_nopriv_update_cart_item_quantity', 'ajax_update_cart_item_quantity');

function ajax_update_cart_item_quantity() {
    // Проверяем наличие данных
    if (!isset($_POST['cart_item_key']) || !isset($_POST['quantity']) || !isset($_POST['security'])) {
        wp_send_json_error('Отсутствуют необходимые данные');
        return;
    }

    // Проверяем nonce для безопасности (сначала наш nonce, потом WooCommerce)
    $security_valid = wp_verify_nonce($_POST['security'], 'kv_ajax_nonce') || wp_verify_nonce($_POST['security'], 'woocommerce-cart');
    
    if (!$security_valid) {
        wp_send_json_error('Ошибка безопасности');
        return;
    }

    $cart_item_key = sanitize_text_field($_POST['cart_item_key']);
    $quantity = intval($_POST['quantity']);

    if (empty($cart_item_key) || $quantity < 0) {
        wp_send_json_error('Неверные данные');
        return;
    }

    // Проверяем, существует ли элемент корзины
    $cart_item = WC()->cart->get_cart_item($cart_item_key);
    if (!$cart_item) {
        wp_send_json_error('Товар не найден в корзине');
        return;
    }

    try {
        $subtotal = '';
        
        if ($quantity == 0) {
            // При количестве 0 не изменяем корзину, просто возвращаем нулевой subtotal
            $subtotal = wc_price(0);
        } else {
            // Обновляем количество товара в корзине
            WC()->cart->set_quantity($cart_item_key, $quantity, false);
            
            // Пересчитываем корзину
            WC()->cart->calculate_totals();
            
            // Получаем обновленную информацию о товаре
            $updated_cart_item = WC()->cart->get_cart_item($cart_item_key);
            if ($updated_cart_item) {
                $_product = $updated_cart_item['data'];
                $subtotal = WC()->cart->get_product_subtotal($_product, $updated_cart_item['quantity']);
            }
        }

        // Получаем обновленные итоги корзины (только для кастомного блока итогов)
        // Не генерируем cart_totals чтобы не перезаписывать блок доставки

        // Получаем фрагменты для обновления других частей страницы
        ob_start();
        woocommerce_mini_cart();
        $mini_cart = ob_get_clean();
        
        $fragments = apply_filters('woocommerce_add_to_cart_fragments', array(
            'div.widget_shopping_cart_content' => '<div class="widget_shopping_cart_content">' . $mini_cart . '</div>'
        ));
        
        // Возвращаем успешный ответ
        wp_send_json_success(array(
            'subtotal' => $subtotal,
            'cart_total' => WC()->cart->get_total(), // Добавляем общую сумму для нашего кастомного блока
            'fragments' => $fragments,
            'cart_hash' => WC()->cart->get_cart_hash(),
            'cart_count' => WC()->cart->get_cart_contents_count()
        ));
    } catch (Exception $e) {
        wp_send_json_error('Ошибка обновления корзины: ' . $e->getMessage());
        return;
    }
}


/**
 * Simple debug trace to **wp-content/debug.log**
 *
 * **usage: _log( $var );**
 */
if (!function_exists('_log')) {
    function _log($log)
    {
        if (true == WP_DEBUG) {
            if (is_array($log) || is_object($log)) {
                error_log(print_r($log, true));
            } else {
                error_log($log);
            }
        } else {
            ob_start();

            echo '[' . date('d-M-Y h:i:s T') . '] ';
            echo "\r\n";

            if (is_array($log) || is_object($log)) {
                print_r($log);
            } else {
                echo ($log);
            }

            echo "\r\n";

            file_put_contents(ABSPATH . 'wp-content/debug.log', ob_get_contents(), FILE_APPEND);

            ob_end_clean();
        }
    }
}


// Принудительно включаем расчет доставки для корзины
add_action('woocommerce_cart_loaded_from_session', 'kv_force_shipping_calculation');
function kv_force_shipping_calculation() {
    if (is_admin()) return;
    
    // Принудительно включаем расчет доставки
    WC()->cart->needs_shipping(true);
    
    // Устанавливаем адрес по умолчанию если не установлен
    if (!WC()->customer->get_shipping_country()) {
        WC()->customer->set_shipping_country('RU');
        WC()->customer->set_shipping_city('Moscow');
    }
}

// Обеспечиваем отображение методов доставки на странице корзины
add_filter('woocommerce_cart_needs_shipping', '__return_true');

// Фильтр для скрытия дублирующих итогов в cart-collaterals при AJAX обновлении
add_filter('woocommerce_cart_totals_order_total_html', 'kv_filter_cart_totals_in_collaterals');
function kv_filter_cart_totals_in_collaterals($order_total_html) {
    // Если мы в контексте cart-collaterals, можем изменить вывод
    if (did_action('woocommerce_cart_collaterals')) {
        // Можно полностью скрыть или изменить
        return ''; // Скрываем дублирующие итоги
    }
    return $order_total_html;
}

// AJAX функция для получения методов доставки
add_action('wp_ajax_get_shipping_methods', 'ajax_get_shipping_methods');
add_action('wp_ajax_nopriv_get_shipping_methods', 'ajax_get_shipping_methods');

function ajax_get_shipping_methods() {
    // Проверяем безопасность
    $security_valid = wp_verify_nonce($_POST['security'], 'kv_ajax_nonce') || wp_verify_nonce($_POST['security'], 'woocommerce-cart');
    
    if (!$security_valid) {
        wp_send_json_error('Ошибка безопасности');
        return;
    }
    
    // Проверяем что корзина не пуста
    if (WC()->cart->is_empty()) {
        wp_send_json_error('Корзина пуста');
        return;
    }
    
    try {
        // Устанавливаем адрес для расчета доставки, если не установлен
        if (!WC()->customer->get_shipping_country()) {
            WC()->customer->set_shipping_country('RU');
            WC()->customer->set_shipping_city('Moscow');
        }
        
        // Принудительно рассчитываем доставку
        WC()->cart->calculate_shipping();
        
        // Получаем HTML методов доставки
        ob_start();
        $packages = WC()->shipping->get_packages();
        foreach ($packages as $i => $package) {
            woocommerce_cart_totals_shipping_html();
            break; // Показываем только первый пакет
        }
        
        // Добавляем поле адреса доставки
        echo '<div class="kv-delivery-address" id="kv-delivery-address">';
        echo '<label for="delivery_address">Адрес доставки:</label>';
        echo '<textarea name="delivery_address" id="delivery_address" placeholder="Введите адрес доставки..."></textarea>';
        echo '</div>';
        
        $shipping_html = ob_get_clean();
        
        wp_send_json_success($shipping_html);
    } catch (Exception $e) {
        wp_send_json_error('Ошибка получения методов доставки: ' . $e->getMessage());
    }
}

/* удаление слова "Доставка" из методов доставки в Корзине */
add_filter( 'woocommerce_shipping_package_name', function( $package_name, $i, $package ) {
    // Убираем слово "Доставка" в блоке методов доставки
    return '';
}, 10, 3 );

// AJAX функция для получения актуальной суммы корзины
add_action('wp_ajax_get_cart_total', 'ajax_get_cart_total');
add_action('wp_ajax_nopriv_get_cart_total', 'ajax_get_cart_total');

function ajax_get_cart_total() {
    // Проверяем безопасность
    $security_valid = wp_verify_nonce($_POST['security'], 'kv_ajax_nonce') || wp_verify_nonce($_POST['security'], 'woocommerce-cart');
    
    if (!$security_valid) {
        wp_send_json_error('Ошибка безопасности');
        return;
    }
    
    // Проверяем что корзина не пуста
    if (WC()->cart->is_empty()) {
        wp_send_json_error('Корзина пуста');
        return;
    }
    
    try {
        // Сохраняем выбранный метод доставки если передан
        if (isset($_POST['shipping_method']) && !empty($_POST['shipping_method'])) {
            $chosen_methods = array($_POST['shipping_method']);
            WC()->session->set('chosen_shipping_methods', $chosen_methods);
        }
        
        // Принудительно пересчитываем корзину с учетом доставки
        WC()->cart->calculate_shipping();
        WC()->cart->calculate_totals();
        
        // Получаем данные корзины
        $cart_data = array(
            'subtotal' => WC()->cart->get_cart_subtotal(),
            'total' => WC()->cart->get_total(),
            'shipping_total' => WC()->cart->get_shipping_total(),
            'cart_count' => WC()->cart->get_cart_contents_count()
        );
        
        wp_send_json_success($cart_data);
    } catch (Exception $e) {
        wp_send_json_error('Ошибка получения данных корзины: ' . $e->getMessage());
    }
}

// Хук для автоматического обновления итогов при изменении доставки
add_action('woocommerce_shipping_method_chosen', 'kv_update_cart_totals_on_shipping_change');
function kv_update_cart_totals_on_shipping_change() {
    if (!is_admin() && !WC()->cart->is_empty()) {
        // Принудительно пересчитываем корзину с новым методом доставки
        WC()->cart->calculate_shipping();
        WC()->cart->calculate_totals();
        
        // Триггерим событие для JavaScript
        add_action('wp_footer', function() {
            echo '<script>jQuery(document).trigger("cart_totals_updated");</script>';
        });
    }
}

// Дополнительный хук для обработки AJAX изменения доставки
add_action('wp_ajax_woocommerce_update_shipping_method', 'kv_ajax_update_shipping_method');
add_action('wp_ajax_nopriv_woocommerce_update_shipping_method', 'kv_ajax_update_shipping_method');

function kv_ajax_update_shipping_method() {
    // Проверяем безопасность
    if (!wp_verify_nonce($_POST['security'], 'kv_ajax_nonce') && !wp_verify_nonce($_POST['security'], 'woocommerce-cart')) {
        wp_send_json_error('Ошибка безопасности');
        return;
    }
    
    if (WC()->cart->is_empty()) {
        wp_send_json_error('Корзина пуста');
        return;
    }
    
    try {
        // Сохраняем выбранный метод доставки
        if (isset($_POST['shipping_method'])) {
            $chosen_methods = array($_POST['shipping_method']);
            WC()->session->set('chosen_shipping_methods', $chosen_methods);
        }
        
        // Пересчитываем корзину
        WC()->cart->calculate_shipping();
        WC()->cart->calculate_totals();
        
        // Возвращаем обновленные данные
        wp_send_json_success(array(
            'total' => WC()->cart->get_total(),
            'subtotal' => WC()->cart->get_cart_subtotal(),
            'shipping_total' => WC()->cart->get_shipping_total(),
            'cart_count' => WC()->cart->get_cart_contents_count()
        ));
    } catch (Exception $e) {
        wp_send_json_error('Ошибка обновления: ' . $e->getMessage());
    }
}

// Убираем отображение мета-данных вариации в корзине, так как единица измерения выводится в отдельной колонке
add_filter( 'woocommerce_get_item_data', function( $item_data, $cart_item_data ) {
    // Возвращаем пустой массив, чтобы не показывать мета-данные вариации в корзине
    return array();
}, 10, 2 );
add_filter( 'woocommerce_get_item_data', function( $item_data, $cart_item_data ) {
    // Возвращаем пустой массив, чтобы не показывать мета-данные вариации в корзине
    return array();
}, 10, 2 );

// Убираем суффиксы вариации из названий товаров в корзине (например "- шт.", "- кг.")
add_filter( 'woocommerce_cart_item_name', function( $product_name, $cart_item, $cart_item_key ) {
    $original_name = $product_name;
    
    // Убираем различные варианты суффиксов единиц измерения  
    // Более широкий паттерн для захвата различных вариантов
    $product_name = preg_replace('/\s*[-–—]\s*(шт\.?|кг\.?|штук|килограмм|pieces?|pcs?\.?)\s*$/ui', '', $product_name);
    
    // Логирование для отладки (можно убрать после исправления)
    if ($original_name !== $product_name) {
        error_log("Cart name filter WORKED: '{$original_name}' -> '{$product_name}'");
    } else {
        error_log("Cart name filter NO CHANGE: '{$product_name}'");
    }
    
    return $product_name;
}, 10, 3 );

// Дополнительный агрессивный фильтр с высоким приоритетом для окончательной очистки
add_filter( 'woocommerce_cart_item_name', function( $product_name, $cart_item, $cart_item_key ) {
    // Ещё один проход - убираем любые паттерны с единицами измерения в конце
    $clean_name = preg_replace('/\s*[-–—]\s*[а-я]+\.?\s*$/ui', '', $product_name);
    
    // Если изменилось - логируем
    if ($clean_name !== $product_name) {
        error_log("Aggressive filter: '{$product_name}' -> '{$clean_name}'");
    }
    
    return $clean_name;
}, 100, 3 );

// AJAX обработка оформления заказа
add_action('wp_ajax_kv_process_checkout', 'kv_process_checkout');
add_action('wp_ajax_nopriv_kv_process_checkout', 'kv_process_checkout');

function kv_process_checkout() {
    // Детальное логирование для отладки
    error_log('=== KV CHECKOUT START [v1.0.6] ===');
    error_log('POST method: ' . $_SERVER['REQUEST_METHOD']);
    error_log('POST data exists: ' . (isset($_POST) ? 'YES' : 'NO'));
    
    // Проверяем наличие security поля
    if (!isset($_POST['security'])) {
        error_log('KV Checkout: Отсутствует поле security в POST');
        wp_send_json_error('Отсутствует поле безопасности');
        wp_die();
    }
    
    error_log('Security field exists: ' . $_POST['security']);
    
    // Проверка nonce
    if (!wp_verify_nonce($_POST['security'], 'kv_ajax_nonce')) {
        error_log('KV Checkout: Ошибка nonce validation');
        wp_send_json_error('Ошибка безопасности');
        wp_die();
    }
    
    error_log('Nonce validation passed');
    
    // Проверка WooCommerce
    if (!function_exists('WC')) {
        error_log('KV Checkout: Функция WC() не существует');
        wp_send_json_error('WooCommerce недоступен');
        wp_die();
    }
    
    error_log('WC() function exists');
    
    if (!WC()) {
        error_log('KV Checkout: WC() возвращает false/null');
        wp_send_json_error('WooCommerce не инициализирован');
        wp_die();
    }
    
    error_log('WC() initialized');
    
    if (!WC()->cart) {
        error_log('KV Checkout: WC()->cart не существует');
        wp_send_json_error('Корзина недоступна');
        wp_die();
    }
    
    error_log('WC()->cart exists');
    
    // ВАЖНО: Сохраняем товары из корзины ПЕРЕД проверкой на пустоту
    $cart_items = WC()->cart->get_cart();
    $cart_count = WC()->cart->get_cart_contents_count();
    
    error_log('Cart items count: ' . $cart_count);
    error_log('Cart items array count: ' . count($cart_items));
    
    if (empty($cart_items) || $cart_count == 0) {
        error_log('KV Checkout: Корзина пуста - items: ' . count($cart_items) . ', count: ' . $cart_count);
        wp_send_json_error('Корзина пуста');
        wp_die();
    }
    
    // Получаем данные из формы (совместимо с PHP 5.6+)
    $name = '';
    if (isset($_POST['customer_name'])) {
        $name = sanitize_text_field($_POST['customer_name']);
    } elseif (isset($_POST['name'])) {
        $name = sanitize_text_field($_POST['name']);
    }
    
    $email = '';
    if (isset($_POST['customer_email'])) {
        $email = sanitize_email($_POST['customer_email']);
    } elseif (isset($_POST['email'])) {
        $email = sanitize_email($_POST['email']);
    }
    
    $phone = '';
    if (isset($_POST['customer_phone'])) {
        $phone = sanitize_text_field($_POST['customer_phone']);
    } elseif (isset($_POST['phone'])) {
        $phone = sanitize_text_field($_POST['phone']);
    }
    
    $delivery_address = '';
    if (isset($_POST['delivery_address'])) {
        $delivery_address = sanitize_textarea_field($_POST['delivery_address']);
    }
    
    error_log('Form data - name: ' . $name . ', email: ' . $email . ', phone: ' . $phone . ', address: ' . $delivery_address);
    
    // Проверка обязательных полей
    if (empty($name) || empty($email) || empty($phone)) {
        error_log('KV Checkout: Пустые обязательные поля');
        wp_send_json_error('Не заполнены обязательные поля');
        wp_die();
    }
    
    // Проверка email
    if (!is_email($email)) {
        error_log('KV Checkout: Некорректный email: ' . $email);
        wp_send_json_error('Некорректный email адрес');
        wp_die();
    }
    
    error_log('All validations passed, starting order creation');
    
    try {
        error_log('Creating order with wc_create_order()');
        // Создаём заказ
        $order = wc_create_order();
        if (!$order) {
            throw new Exception('Не удалось создать заказ');
        }
        
        error_log('Order created with ID: ' . $order->get_id());
        
        // Добавляем товары из СОХРАНЁННОЙ корзины
        error_log('Adding cart items to order');
        foreach ($cart_items as $cart_item) {
            $order->add_product($cart_item['data'], $cart_item['quantity']);
        }
        
        error_log('Setting customer data');
        // Устанавливаем данные клиента
        $order->set_billing_first_name($name);
        $order->set_billing_email($email);
        $order->set_billing_phone($phone);
        
        if (!empty($delivery_address)) {
            $order->set_shipping_address_1($delivery_address);
            $order->set_billing_address_1($delivery_address);
        }
        
        error_log('Calculating totals and saving order');
        // Сохраняем
        $order->calculate_totals();
        $order->set_status('processing', 'Заказ оформлен через корзину');
        $order->save();
        
        error_log('Order saved, clearing cart AFTER order creation');
        // Очищаем корзину ТОЛЬКО ПОСЛЕ успешного создания заказа
        WC()->cart->empty_cart();
        
        error_log('Sending notifications');
        // Отправляем уведомления (с обработкой ошибок)
        try {
            // Получаем экземпляр email-ов WooCommerce
            $mailer = WC()->mailer();
            if ($mailer) {
                // Получаем все доступные email классы
                $emails = $mailer->get_emails();
                
                // Отправляем уведомление клиенту о новом заказе
                if (isset($emails['WC_Email_Customer_Processing_Order'])) {
                    $emails['WC_Email_Customer_Processing_Order']->trigger($order->get_id());
                    error_log('Customer processing email sent');
                }
                
                // Отправляем уведомление администратору о новом заказе
                if (isset($emails['WC_Email_New_Order'])) {
                    $emails['WC_Email_New_Order']->trigger($order->get_id());
                    error_log('Admin new order email sent');
                }
            }
        } catch (Exception $mail_error) {
            error_log('KV Checkout: Ошибка отправки email: ' . $mail_error->getMessage());
            // Не прерываем выполнение
        }
        
        error_log('KV Checkout: Заказ успешно оформлен');
        
        wp_send_json_success(array(
            'message' => 'Заказ успешно оформлен',
            'order_id' => $order->get_id(),
            'redirect_url' => $order->get_checkout_order_received_url()
        ));
        
    } catch (Exception $e) {
        error_log('KV Checkout Error: ' . $e->getMessage());
        error_log('KV Checkout Error Stack: ' . $e->getTraceAsString());
        wp_send_json_error('Ошибка при создании заказа: ' . $e->getMessage());
    }
    
    wp_die();
}

// Кастомизация страницы благодарности (order-received)
add_action('wp_enqueue_scripts', 'kv_thankyou_page_styles');

function kv_thankyou_page_styles() {
    // Загружаем стили только на странице благодарности
    if (is_wc_endpoint_url('order-received')) {
        wp_add_inline_style('woocommerce-general', '
            /* Дополнительные стили для страницы благодарности */
            .woocommerce-order {
                max-width: 1200px;
                margin: 0 auto;
                padding: 20px;
            }
            
            .woocommerce-thankyou-order-received {
                background-color: #d4edda;
                border: 1px solid #c3e6cb;
                color: #155724;
                padding: 15px;
                border-radius: 8px;
                margin-bottom: 30px;
                font-size: 16px;
                font-weight: 500;
            }
            
            .woocommerce-order-overview {
                background-color: #f8f9fa;
                border: 1px solid #dee2e6;
                border-radius: 8px;
                padding: 20px;
                margin-bottom: 30px;
                list-style: none;
                display: flex;
                flex-wrap: wrap;
                gap: 15px;
            }
            
            .woocommerce-order-overview li {
                background-color: white;
                padding: 10px 15px;
                border-radius: 6px;
                border: 1px solid #e9ecef;
                flex: 1;
                min-width: 150px;
                text-align: center;
            }
            
            .woocommerce-order-overview strong {
                color: #ff6b35;
                font-weight: 600;
            }
            
            @media (max-width: 768px) {
                .woocommerce-order-overview {
                    flex-direction: column;
                }
                
                .woocommerce-order-overview li {
                    flex: none;
                }
            }
        ');
    }
}

// Убираем стандартную таблицу деталей заказа, если используем кастомную
add_action('init', 'kv_customize_thankyou_page');

function kv_customize_thankyou_page() {
    // Убираем стандартные детали заказа, так как у нас есть кастомная таблица
    remove_action('woocommerce_thankyou', 'woocommerce_order_details_table', 10);
}

// Функция для получения единицы измерения товара (соответствует логике в корзине)
function kv_get_product_unit($product, $item = null) {
    $unit = '';
    
    if ($product->is_type('variation')) {
        // Для вариаций используем атрибут pa_sht-kg
        $unit = $product->get_attribute('pa_sht-kg');
    } elseif ($product->is_type('variable') && $item) {
        // Для вариативных товаров получаем единицу из метаданных заказа
        $item_meta = $item->get_meta_data();
        foreach ($item_meta as $meta) {
            if ($meta->key === 'attribute_pa_sht-kg' || $meta->key === 'pa_sht-kg') {
                $unit = $meta->value;
                break;
            }
        }
    }
    
    return $unit ? $unit : '-';
}
