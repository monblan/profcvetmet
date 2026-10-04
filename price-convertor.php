<?php
/*
Plugin Name: Price convertor
Description: создание файла для импорта в woocommerce
Version: 1.0
Author: kvv-dev
*/

// Добавляем пункт меню в админку
add_action('admin_menu', function() {
    add_submenu_page(
        'tools.php',
        'Price convertor',
        'Price convertor',
        'manage_options',
        'price-convertor',
        'price_convertor_admin_page'
    );
});

// Функция отображения страницы
function price_convertor_admin_page() {
    echo '<link rel="stylesheet" href="' . plugins_url('assets/style.css', __FILE__) . '?v=2" />';
    echo '<script src="' . includes_url('js/jquery/jquery.js') . '"></script>';
    echo '<script src="' . plugins_url('assets/script.js', __FILE__) . '?v=2" defer></script>';
    echo '<div class="wrap">';
    echo '<h1>Конвертор прайса металлопроката</h1>';
    // Индикатор процесса
    echo '<div class="kv-loader-wrap"><div class="kv-spinner"></div><span class="kv-loader-text">Пожалуйста, подождите...</span></div>';
    echo '<form method="post" class="kv-form-scv" enctype="multipart/form-data">';
    echo '<label for="kv_filename">Выберите CSV файл:</label> ';
    echo '<input type="file" id="kv_filename" name="kv_filename" accept=".csv" onchange="if(this.files.length){document.getElementById(\'run_btn\').style.display=\'inline-block\';}else{document.getElementById(\'run_btn\').style.display=\'none\';}" /> ';
    echo '<div class="descr-cvs">Внимание! Файл должен быть в формате CSV, кодировка - UTF-8, разделитель - точка с запятой.</div>';
    echo '<input type="submit" name="run_csv" id="run_btn" value="Выполнить" class="button button-primary" style="display:none;" />';
    echo '</form>';

    global $kv_missing_thumbnails, $kv_missing_categories;

    $kv_missing_thumbnails = [];
    $kv_missing_categories = [];

    if (isset($_POST['run_csv']) && !empty($_FILES['kv_filename']['tmp_name'])) {
        $src = $_FILES['kv_filename']['tmp_name'];
        $result = kv_test_process_csv($src);
        $success = false;
        $variable_count = 0;
    
        if (is_array($result) && isset($result['success']) && $result['success'] === true) {
            $success = true;
            $variable_count = (int)$result['variable_count'];
        } elseif ($result === true) {
            $success = true;
        }
    
        if ($success) {
            echo '<div class="notice notice-success"><p>Файл metall-import.csv успешно создан в папке wp-content.';
            
            if ($variable_count > 0) {
                echo ' Обработано товаров: ' . intval($variable_count);
            }
            echo '</p></div>';
            echo '<p><a href="' . content_url('metall-import.csv') . '" download="metall-import.csv" class="button button-primary">Скачать файл metall-import.csv</a></p>';
            if (!empty($kv_missing_thumbnails)) {
                echo '<div class="notice notice-warning"><p>Не найдены миниатюры для категорий: ' . esc_html(implode(', ', $kv_missing_thumbnails)) . '</p></div>';
            }
            if (!empty($kv_missing_categories)) {
                echo '<div class="notice notice-warning"><p>На сайте отсутствуют категории: ' . esc_html(implode(', ', $kv_missing_categories)) . '</p></div>';
            }
        } else {
            echo '<div class="notice notice-error"><p>Ошибка: ' . esc_html($result) . '</p></div>';
        }
    }

    echo '</div>';
}

function kv_test_process_csv($src) {
    global $kv_missing_thumbnails, $kv_missing_categories;
    
    $out = WP_CONTENT_DIR . '/metall-import.csv';
    
    if (!file_exists($src)) return 'Файл не найден.';
    
    $rows = [];
    
    $handle = fopen($src, 'r');
    
    if (!$handle) return 'Не удалось открыть файл.';
    
    $header = fgetcsv($handle, 0, ';');
    
    if (!$header) return 'Пустой или некорректный CSV.';
    // Удаляем пробелы в начале и в конце у всех заголовков
    $header = array_map('trim', $header);
    $variable_keys = [];
    $first_variable = true;
    $kv_missing_thumbnails = [];
    $kv_missing_categories = [];
    $variable_count = 0;
    
    while (($data = fgetcsv($handle, 0, ';')) !== false) {
        $row = array_combine($header, $data);
    
        if (!$row) continue;
        // --- Родительский товар (variable) ---
        $parent = [];
        $parent['Тип'] = 'variable';
        $parent['Имя'] = $row['Наименование'];
        $parent['Артикул'] = $row['Наименование'];
        $parent['Опубликован'] = 1;
        $parent['Статус налога'] = 'taxable';
        $parent['Налоговый класс'] = '';
        $parent['Видимость в каталоге'] = 'visible';
        $parent['В наличии?'] = 1;
        $parent['Базовая цена'] = '';
    
        if (trim($row['Материал']) === 'Алюминий анодированный') {
            $type = trim($row['Тип изделия']);
    
            switch ($type) {
                case 'Полоса':
                    $cat = 'Алюминий > Анодированный профиль > Анодированная полоса, Анодированный профиль';
                    break;
                case 'Труба квадратная':
                    $cat = 'Алюминий > Анодированный профиль > Анодированная труба квадратная, Анодированный профиль';
                    break;
                case 'Труба круглая':
                    $cat = 'Алюминий > Анодированный профиль > Анодированная труба круглая, Анодированный профиль';
                    break;
                case 'Труба профильная':
                    $cat = 'Алюминий > Анодированный профиль > Труба профильная, Анодированный профиль';
                    break;
                case 'Уголок':
                    $cat = 'Алюминий > Анодированный профиль > Анодированный уголок, Анодированный профиль';
                    break;
                case 'Швеллер':
                    $cat = 'Алюминий > Анодированный профиль > Анодированный швеллер, Анодированный профиль';
                    break;
                case 'Лист':
                    $cat = 'Алюминий, Алюминий > Анодированный Лист';
                    break;
                default:
                    $cat = 'Алюминий > Анодированный профиль, Анодированный профиль';
                    break;
            }
        } else {
            $material = trim($row['Материал']);
            if ($material === 'Нержавеющая сталь') {
                $material = 'Нержавейка';
            }
            $cat = $material . ', ' . $material . ' > ' . $row['Тип изделия'];
        }
    
        $parent['Категории'] = $cat;
        $parent['Родительский'] = '';
        // --- Получаем миниатюру самой нижней категории и проверяем существование категории ---
        $cat_paths = explode(',', $cat);
        $thumbnail_url = '';
        $found_thumbnail = false;
        $parent_categories = ['латунь', 'алюминий', 'бронза', 'медь', 'нержавейка'];
    
        foreach ($cat_paths as $cat_path) {
            $cat_parts = array_map('trim', explode('>', $cat_path));
            $last_cat = end($cat_parts);
            $is_parent = in_array(mb_strtolower($last_cat), $parent_categories, true);
            // Не ищем миниатюру и не проверяем существование для "Анодированный профиль"
            if (mb_strtolower($last_cat) === 'анодированный профиль') {
                continue;
            }
            // Не ищем миниатюру и не добавляем в отсутствующие, если это родительская категория и путь не содержит вложенности
            if ($is_parent && count($cat_parts) === 1) {
                continue;
            }
            // Для поиска категорий также заменяем 'Нержавеющая сталь' на 'Нержавейка'
            if ($last_cat === 'Нержавеющая сталь') {
                $last_cat = 'Нержавейка';
            }
            // --- Строгая проверка по иерархии ---
            $found_term = null;
            $terms = get_terms([
                'taxonomy' => 'product_cat',
                'name' => $last_cat,
                'hide_empty' => false,
            ]);
    
            if (!is_wp_error($terms) && !empty($terms)) {
                foreach ($terms as $term) {
                    $parent_ok = true;
                    $current_term = $term;
                    // Проверяем иерархию снизу вверх
                    for ($i = count($cat_parts) - 2; $i >= 0; $i--) {
                        $parent_name = trim($cat_parts[$i]);
                        if ($parent_name === 'Нержавеющая сталь') {
                            $parent_name = 'Нержавейка';
                        }
                        if ($current_term->parent == 0) {
                            $parent_ok = false;
                            break;
                        }
                        $parent_term = get_term($current_term->parent, 'product_cat');
                        if (!$parent_term || is_wp_error($parent_term) || $parent_term->name !== $parent_name) {
                            $parent_ok = false;
                            break;
                        }
                        $current_term = $parent_term;
                    }
                    if ($parent_ok) {
                        $found_term = $term;
                        break;
                    }
                }
            }
    
            if (!$found_term) {
                $kv_missing_categories[$last_cat] = $last_cat;
            } else {
                $thumb_id = get_term_meta($found_term->term_id, 'thumbnail_id', true);
                if ($thumb_id) {
                    $full_url = wp_get_attachment_url($thumb_id);
                    if ($full_url) {
                        $thumbnail_url = $full_url;
                        $found_thumbnail = true;
                        break; // используем первую найденную миниатюру
                    }
                }
            }
            // Если не нашли миниатюру, добавляем в список отсутствующих
            if (!$found_thumbnail) {
                $kv_missing_thumbnails[$last_cat] = $last_cat;
            }
        }
    
        $parent['Изображения'] = $thumbnail_url;
        $parent['Название атрибута 1'] = 'Марка';
        $parent['Значения атрибутов 1'] = $row['Марка сплава'];
        $parent['Видимость атрибута 1'] = 1;
        $parent['Глобальный атрибут 1'] = 1;
        $parent['Название атрибута 2'] = 'Размер 1';
        $parent['Значения атрибутов 2'] = $row['Размер 1'];
        $parent['Видимость атрибута 2'] = 1;
        $parent['Глобальный атрибут 2'] = 1;
        $parent['Название атрибута 3'] = 'Размер 2';
        $parent['Значения атрибутов 3'] = $row['Размер 2'];
        $parent['Видимость атрибута 3'] = 1;
        $parent['Глобальный атрибут 3'] = 1;
        $parent['Название атрибута 4'] = 'Толщина';
        $parent['Значения атрибутов 4'] = $row['Толщина'];
        $parent['Видимость атрибута 4'] = 1;
        $parent['Глобальный атрибут 4'] = 1;
        $parent['Название атрибута 5'] = 'Длина';
        $parent['Значения атрибутов 5'] = $row['Длина'];
        $parent['Видимость атрибута 5'] = 1;
        $parent['Глобальный атрибут 5'] = 1;
        $parent['Название атрибута 6'] = 'Вес';
        $parent['Значения атрибутов 6'] = $row['Вес'];
        $parent['Видимость атрибута 6'] = 1;
        $parent['Глобальный атрибут 6'] = 1;
        $parent['Название атрибута 7'] = 'шт/кг';
        $parent['Значения атрибутов 7'] = 'кг., шт.';
        $parent['Видимость атрибута 7'] = 1;
        $parent['Глобальный атрибут 7'] = 1;
        $parent['Атрибут 7 по умолчанию'] = 'шт.';
        $parent['_price_sht'] = $row['Цена'];
        $parent['_price_kg'] = $row['Цена руб/кг'];
        $parent['_marka_name'] = $row['Марка сплава'];
        $parent['_razmer_1'] = $row['Размер 1'];
        $parent['_razmer_2'] = $row['Размер 2'];
        $parent['_tolshina'] = $row['Толщина'];
        $parent['_dlina'] = $row['Длина'];
        $parent['_ves'] = $row['Вес'];
    
        if ($first_variable) {
            $variable_keys = array_keys($parent);
            $first_variable = false;
        }
    
        $rows[] = $parent;
        // --- Вариация 1 (шт.) ---
        $var1 = array_fill_keys($variable_keys, '');
        $var1['Тип'] = 'variation';
        $var1['Имя'] = $row['Наименование'] . ' - шт.';
        $var1['Артикул'] = $var1['Имя'];
        $var1['Опубликован'] = 1;
        $var1['Статус налога'] = 'taxable';
        $var1['Налоговый класс'] = 'parent';
        $var1['Видимость в каталоге'] = 'visible';
        $var1['В наличии?'] = 1;
        $var1['Базовая цена'] = $row['Цена'];
        $var1['Родительский'] = $row['Наименование'];
        $var1['Название атрибута 7'] = 'шт/кг';
        $var1['Значения атрибутов 7'] = 'шт.';
        $var1['Глобальный атрибут 7'] = 1;
        $rows[] = $var1;
        // --- Вариация 2 (кг.) ---
        $var2 = array_fill_keys($variable_keys, '');
        $var2['Тип'] = 'variation';
        $var2['Имя'] = $row['Наименование'] . ' - кг.';
        $var2['Артикул'] = $var2['Имя'];
        $var2['Опубликован'] = 1;
        $var2['Статус налога'] = 'taxable';
        $var2['Налоговый класс'] = 'parent';
        $var2['Видимость в каталоге'] = 'visible';
        $var2['В наличии?'] = 1;
        $var2['Базовая цена'] = $row['Цена руб/кг'];
        $var2['Родительский'] = $row['Наименование'];
        $var2['Название атрибута 7'] = 'шт/кг';
        $var2['Значения атрибутов 7'] = 'кг.';
        $var2['Глобальный атрибут 7'] = 1;
        $rows[] = $var2;
        $variable_count++;
    }
    
    fclose($handle);
    // Запись итогового файла
    if (empty($rows)) return 'Нет данных для записи.';
    // Формируем заголовки для WooCommerce
    $header = array_keys($rows[0]);
    // Добавим "Мета: " к последним 8 колонкам
    $header_count = count($header);
    
    for ($i = $header_count - 8; $i < $header_count; $i++) {
        if (isset($header[$i])) {
            $header[$i] = 'Мета: ' . $header[$i];
        }
    }
    
    $out_handle = fopen($out, 'w');
    
    if (!$out_handle) return 'Не удалось создать файл metall-import.csv.';
    
    fputcsv($out_handle, $header, ';');
    
    foreach ($rows as $r) {
        // Заменяем запятые на точки в числовых полях
        foreach ($r as $k => $v) {
            if (is_numeric(str_replace([',','.'], '', $v))) {
                $r[$k] = str_replace(',', '.', $v);
            }
        }
        fputcsv($out_handle, $r, ';');
    }
  
    fclose($out_handle);
  
    return ['success' => true, 'variable_count' => $variable_count];
} 