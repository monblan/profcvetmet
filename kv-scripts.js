jQuery(document).ready(function ($) {
    jQuery(".sb-search-submit, .search-placeholder").click(function (e) {
        var formElement = jQuery(".woocommerce-product-search");

        if (formElement.hasClass("form-open")) {
            if (jQuery('#search').val().trim() == '') {
                formElement.removeClass('form-open');

                e.preventDefault();
            }
        } else {
            formElement.addClass('form-open');
            $('#search').focus();

            e.preventDefault();
        }
    });

    $('#search').on('mouseleave', function () {
        $(this).closest('form').removeClass('form-open');
    });

    jQuery('input[type="tel"]').inputmask({ mask: "+7(999) 999-99-99" });

    /*карточка товара*/

    jQuery('.quantity-mod').each(function () {
        var spinner = jQuery(this),
            input = spinner.find('input[type="number"]'),
            btnUp = spinner.find('.quantity-up'),
            btnDown = spinner.find('.quantity-down'),
            min = input.attr('min'),
            max = input.attr('max');

        btnUp.click(function () {
            var oldValue = parseFloat(input.val());

            if (oldValue >= max) {
                var newVal = oldValue;
            } else {
                var newVal = oldValue + 1;
            }
            spinner.find("input").val(newVal);
            spinner.find("input").trigger("change");
        });

        btnDown.click(function () {
            var oldValue = parseFloat(input.val());

            if (oldValue <= min) {
                var newVal = oldValue;
            } else {
                var newVal = oldValue - 1;
            }
            spinner.find("input").val(newVal);
            spinner.find("input").trigger("change");
        });
    });


    /*добавление в корзину из категории*/
    $(document).on('click', '.add-to-cart-btn', function () {
        const $btn = $(this);
        const $row = $btn.closest('tr');
        const productId = $btn.data('product-id');
        const variations = $btn.data('variations');
        const qty = $row.find('input.qty').val();
        const unit = $row.find('select.unit-selector').val();
        const variationId = variations[unit];

        if (!variationId) {
            alert('Вариация не найдена');
            return;
        }

        $.ajax({
            url: wc_add_to_cart_params.ajax_url,
            type: 'POST',
            data: {
                action: 'custom_add_to_cart',
                product_id: productId,
                qty: qty,
                variation_id: variationId
            },
            success: function (response) {
                if (response && response.fragments) {
                    $.each(response.fragments, function (key, value) {
                        $(key).replaceWith(value);
                    });

                    $(document.body).trigger('wc_fragment_refresh');

                    // Показываем модальное окно FancyBox
                    $.fancybox.open({
                        src: '<div style="color: #333; border: 4px solid orange; border-radius: 12px; background-color: #fff; overflow: hidden; font-size:18px;"><div style="text-align: center;">Товар добавлен в корзину</div></div>',
                        type: 'html',
                        opts: {
                            smallBtn: true, // Показать стандартную кнопку закрытия
                            toolbar: true,  // Скрыть панель инструментов
                            touch: false,    // Отключить закрытие свайпом на мобильных
                            afterShow: function (instance, current) {
                                setTimeout(function () {
                                    $.fancybox.close();
                                }, 2500);
                            }
                        }
                    });
                }
            },
            error: function () {
                alert('Ошибка запроса');
            }
        });
    });


    /*страница архива. "Загрузить еще"*/
    $('#load-more-products').on('click', function (e) {
        e.preventDefault();

        const $btn = $(this);
        const nextPage = parseInt($btn.data('page')) + 1;
        const categoryId = $btn.data('category');

        const params = new URLSearchParams(window.location.search);
        const filters = {};
        let orderbyCustom = '';

        params.forEach((value, key) => {
            const cleanKey = key.replace(/\[\]$/, '');

            if (cleanKey === 'orderby_custom') {
                orderbyCustom = value;
            } else {
                filters[cleanKey] = filters[cleanKey] || [];
                filters[cleanKey].push(value);
            }
        });


        $.ajax({
            url: wc_add_to_cart_params.ajax_url,
            type: 'POST',
            data: {
                action: 'load_more_products',
                page: nextPage,
                category_id: categoryId,
                filters: filters,
                orderby_custom: orderbyCustom // 💥 Передаём сортировку отдельно!
            },
            success: function (res) {
                if (res.success) {
                    $('.woocommerce-products-table tbody').append(res.data);
                    $btn.data('page', nextPage);
                } else {
                    $btn.hide();
                }
            }
        });
    });

});


/*открывает панель свойств атрибута*/
jQuery(document).ready(function ($) {
    const $panel = $('#attribute-filter-panel');

    // Закрытие панели
    $(document).on('click', '.close-filter', function () {
        $panel.fadeOut(150);
    });

    // Закрытие по клику вне панели
    $(document).mouseup(function (e) {
        if (!$panel.is(e.target) && $panel.has(e.target).length === 0) {
            $panel.fadeOut(150);
        }
    });
});


/*применить фильтр*/
jQuery(document).ready(function ($) {
    $(document).on('click', '.apply-filter', function (e) {
        e.preventDefault();

        const params = new URLSearchParams(window.location.search);

        // Очистим старые фильтры всех атрибутов
        $('.attribute-filter-panel input[type="checkbox"]').each(function () {
            const attr = $(this).attr('name').replace('[]', '');
            params.delete(attr);
            params.delete(attr + '[]');
        });

        // Соберём отмеченные чекбоксы
        $('.attribute-filter-panel input[type="checkbox"]:checked').each(function () {
            const attr = $(this).attr('name').replace('[]', '');
            const value = $(this).val();

            if (value) {
                params.append(attr + '[]', value);
            }
        });

        // Обновим URL (только с активными фильтрами)
        window.location.href = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
    });


    $(document).on('click', '.reset-filter', function (e) {
        e.preventDefault();

        // Панель конкретного атрибута
        const $panel = $(this).closest('.attribute-filter-panel');

        // Снимаем все чекбоксы в этой панели
        $panel.find('input[type="checkbox"]').prop('checked', false);

        // Получаем имя атрибута (без []), например: marka
        const attrName = $panel.find('input[type="checkbox"]').first().attr('name').replace('[]', '');

        // Получаем текущие параметры URL
        const params = new URLSearchParams(window.location.search);

        // Удаляем только параметры этого атрибута
        params.delete(attrName);
        params.delete(attrName + '[]');

        // Формируем новый URL
        const newQuery = params.toString();
        const newUrl = window.location.pathname + (newQuery ? '?' + newQuery : '');

        // Обновляем страницу
        window.location.href = newUrl;
    });


    /*открываем панель свойств атрибута для фильтрации*/
    $('.filter-toggle').on('click', function (e) {
        e.preventDefault();

        const attr = $(this).data('attribute');
        let values = attributeTerms[attr] || [];

        // 🧠 Сортируем числовые термы, если атрибут относится к числовым
        if (['razmer-1', 'razmer-2', 'dlina', 'tolshina'].includes(attr)) {
            values = values.slice().sort((a, b) => parseFloat(a) - parseFloat(b));
        }

        const params = new URLSearchParams(window.location.search);
        const checkedValues = params.getAll(attr + '[]');

        const checkboxes = values.map(function (val) {
            const checked = checkedValues.includes(val) ? 'checked' : '';
            return `
			<label>
				<input type="checkbox" name="${attr}[]" value="${val}" ${checked}> ${val}
			</label>
		`;
        }).join('');

        $('#attribute-filter-panel').html(`
		<div class="filter-popup">
			<div class="filter-popup-header">
				<strong>${$(this).text()}</strong>
				<button class="close-filter" type="button">×</button>
			</div>
			<div class="filter-popup-body">
				${checkboxes}
			</div>
			<div class="filter-popup-footer">
				<button class="apply-filter">ОК</button>
				<button class="reset-filter" type="button">СБРОС</button>
			</div>
		</div>
	`).fadeIn(150);
    });
});


/*заполнение панели фильтра для шаблона архива*/
jQuery(document).ready(function ($) {
    const $filterWrap = $('.wrap-filter-panel');
    const $attrWrapper = $filterWrap.find('.filter-panel__attr');
    const params = new URLSearchParams(window.location.search);

    const activeFilters = {};

    for (const [key, value] of params.entries()) {
        // 🧠 Игнорируем технические параметры
        if (key === 'orderby_custom' || key === 'page') continue;

        const cleanKey = key.replace(/\[\]$/, '');
        if (!activeFilters[cleanKey]) activeFilters[cleanKey] = [];
        activeFilters[cleanKey].push(value);
    }

    // Метки атрибутов (можно дополнить)
    const attributeLabels = {
        'marka': 'Марка',
        'razmer-1': 'Размер 1',
        'razmer-2': 'Размер 2',
        'dlina': 'Длина',
        'tolshina': 'Толщина',
        'ves': 'Вес'
    };

    let hasFilters = false;

    for (const slug in activeFilters) {
        const terms = activeFilters[slug];
        if (!terms.length) continue;

        const label = (typeof acfLabels !== 'undefined' && acfLabels[slug]) ? acfLabels[slug] : (attributeLabels[slug] || slug);

        const $item = $(`
			<div class="filter-panel__attr-item">
				<span class="filter-panel__attr-slug">${label}</span>
				<span class="filter-panel__attr-terms">${terms.join(', ')}</span>
			</div>
		`);

        $attrWrapper.append($item);
        hasFilters = true;
    }

    if (hasFilters) {
        $filterWrap.show();
    } else {
        $filterWrap.hide();
    }

    // Кнопка "Сбросить фильтр"
    $filterWrap.find('.filter-panel__btn button').on('click', function () {
        window.location.href = window.location.pathname;
    });
});


/*заполнение  списка сортировки*/
jQuery(document).ready(function ($) {
    if (typeof sortingAttributes !== 'undefined') {
        const $sortingSelect = $('#sorting-attrib');

        // Очищаем от стартового <option>
        $sortingSelect.empty();

        // Добавляем "По умолчанию" пункт
        $sortingSelect.append('<option value="">Выберите сортировку</option>');

        $sortingSelect.append('<option value="">Сортировать по умолчанию</option>');

        $.each(sortingAttributes, function (slug, label) {
            $sortingSelect.append(`<option value="${slug}_asc">${label} по возрастанию</option>`);
            $sortingSelect.append(`<option value="${slug}_desc">${label} по убыванию</option>`);
        });
    }


    $('#sorting-attrib').on('change', function () {
        const selectedSorting = $(this).val();
        const params = new URLSearchParams(window.location.search);

        if (selectedSorting) {
            params.set('orderby_custom', selectedSorting); // добавляем/заменяем параметр сортировки
        } else {
            params.delete('orderby_custom'); // если выбрал "по умолчанию" — убираем параметр
        }

        // Перезагружаем страницу с новым параметром
        window.location.search = params.toString();
    });
});


/*автоматический пересчет корзины*/
jQuery(document).ready(function ($) {
    // Функция для управления отображением поля адреса доставки
    function toggleDeliveryAddress() {
        const $addressBlock = $('#kv-delivery-address');
        const $selectedShipping = $('input[name^="shipping_method["]:checked');

        if ($selectedShipping.length > 0) {
            const shippingValue = $selectedShipping.val();
            const shippingLabel = $selectedShipping.closest('li').find('label').text().toLowerCase();

            // Скрываем поле адреса если выбран самовывоз
            if (shippingLabel.includes('самовывоз') || shippingLabel.includes('pickup')) {
                $addressBlock.addClass('hidden');
            } else {
                $addressBlock.removeClass('hidden');
            }
        }
    }

    // Функция для обновления общей стоимости заказа
    function updateCartTotals() {
        const ajaxUrl = typeof kv_ajax_params !== 'undefined' ? kv_ajax_params.ajax_url : (typeof wc_add_to_cart_params !== 'undefined' ? wc_add_to_cart_params.ajax_url : '');
        const securityNonce = typeof kv_ajax_params !== 'undefined' ? kv_ajax_params.nonce : '';

        if (!ajaxUrl || !securityNonce) {
            return;
        }

        const selectedShippingMethod = $('input[name^="shipping_method["]:checked').val();

        // Показываем индикатор загрузки
        $('.kv-cart-total').addClass('updating');

        // Используем нашу кастомную AJAX функцию для получения актуальных итогов
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'get_cart_total',
                shipping_method: selectedShippingMethod,
                security: securityNonce
            },
            success: function (response) {
                if (response.success && response.data && response.data.total) {
                    $('.kv-cart-total .total-amount').html(response.data.total);
                    console.log('Итоги корзины обновлены');
                } else {
                    // Fallback - пробуем синхронизацию с DOM
                    setTimeout(syncCustomTotalWithWooCommerce, 200);
                }
                $('.kv-cart-total').removeClass('updating');
            },
            error: function () {
                console.log('Ошибка обновления итогов корзины');
                // Fallback - пробуем синхронизацию с DOM
                setTimeout(syncCustomTotalWithWooCommerce, 200);
                $('.kv-cart-total').removeClass('updating');
            }
        });
    }

    // Вызываем функцию при загрузке страницы
    setTimeout(toggleDeliveryAddress, 500);

    // Обработчик изменения метода доставки
    $(document).on('change', 'input[name^="shipping_method["]', function () {
        console.log('Метод доставки изменен, обновляем итоги...');

        // Обновляем отображение поля адреса
        toggleDeliveryAddress();

        // Обновляем итоги корзины
        updateCartTotals();

        // Триггерим стандартное WooCommerce событие обновления корзины
        $('body').trigger('update_checkout');
    });

    // Обработчик стандартных WooCommerce событий
    $(document).on('updated_wc_div updated_checkout updated_cart_totals', function () {
        // Обновляем кастомный блок с задержкой для синхронизации
        setTimeout(function () {
            syncCustomTotalWithWooCommerce();
            toggleDeliveryAddress();
        }, 300);
    });

    // Дополнительный обработчик для событий изменения корзины
    $(document).on('wc_cart_totals_refreshed', function () {
        setTimeout(syncCustomTotalWithWooCommerce, 100);
    });

    // Функция синхронизации кастомного блока с WooCommerce
    function syncCustomTotalWithWooCommerce() {
        // Поскольку стандартные итоги WooCommerce скрыты CSS,
        // сразу используем AJAX запрос для получения актуальных данных

        const ajaxUrl = typeof kv_ajax_params !== 'undefined' ? kv_ajax_params.ajax_url : '';
        const securityNonce = typeof kv_ajax_params !== 'undefined' ? kv_ajax_params.nonce : '';

        if (!ajaxUrl || !securityNonce) {
            return;
        }

        // Показываем индикатор загрузки
        $('.kv-cart-total').addClass('updating');

        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'get_cart_total',
                shipping_method: $('input[name^="shipping_method["]:checked').val(),
                security: securityNonce
            },
            success: function (response) {
                if (response.success && response.data && response.data.total) {
                    $('.kv-cart-total .total-amount').html(response.data.total);
                }
                $('.kv-cart-total').removeClass('updating');
            },
            error: function () {
                $('.kv-cart-total').removeClass('updating');
            }
        });
    }

    // Обработчик изменения количества в корзине
    $(document).on('change', '.kv-cart-checkout input[name^="cart["][name$="[qty]"]', function () {
        const $input = $(this);
        const $row = $input.closest('tr');
        const cartItemKey = $input.attr('name').match(/cart\[([^\]]+)\]/)[1];
        const newQuantity = parseInt($input.val()) || 0; // Разрешаем 0

        console.log('Updating cart item:', cartItemKey, 'to quantity:', newQuantity);

        // Показываем индикатор загрузки
        $row.addClass('updating');

        // Определяем AJAX URL
        let ajaxUrl = '';
        let securityNonce = '';

        if (typeof kv_ajax_params !== 'undefined') {
            ajaxUrl = kv_ajax_params.ajax_url;
            securityNonce = kv_ajax_params.nonce;
        } else if (typeof wc_add_to_cart_params !== 'undefined') {
            ajaxUrl = wc_add_to_cart_params.ajax_url;
            const nonceField = $('#woocommerce-cart-nonce');
            if (nonceField.length > 0) {
                securityNonce = nonceField.val();
            }
        } else {
            console.error('Не найдены AJAX параметры');
            alert('Ошибка: не найдены AJAX параметры');
            $row.removeClass('updating');
            return;
        }

        if (!ajaxUrl || !securityNonce) {
            console.error('AJAX URL или nonce пусты');
            alert('Ошибка: отсутствуют параметры безопасности');
            $row.removeClass('updating');
            return;
        }


        // AJAX запрос для обновления корзины
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'update_cart_item_quantity',
                cart_item_key: cartItemKey,
                quantity: newQuantity,
                security: securityNonce
            },
            beforeSend: function () {
                // Показываем индикатор загрузки для блока доставки
                $('.cart-collaterals').addClass('loading');
            },
            success: function (response) {
                if (response.success) {
                    // Обновляем подытог для строки (всегда, даже при 0)
                    if (newQuantity === 0) {
                        // При количестве 0 принудительно обнуляем подытог
                        const zeroPrice = '<span class="woocommerce-Price-amount amount"><bdi>0&nbsp;<span class="woocommerce-Price-currencySymbol">&#8381;</span></bdi></span>';
                        $row.find('.product-subtotal').html(zeroPrice);
                    } else if (response.data.subtotal !== undefined) {
                        $row.find('.product-subtotal').html(response.data.subtotal);
                    }

                    // Обновляем общую сумму в нашем кастомном блоке
                    if (response.data.cart_total) {
                        $('.kv-cart-total .total-amount').html(response.data.cart_total);
                    }

                    // Обновляем фрагменты корзины (счетчик товаров в шапке и т.д.)
                    if (response.data.fragments) {
                        $.each(response.data.fragments, function (key, value) {
                            $(key).replaceWith(value);
                        });
                    }

                    // Убираем индикаторы загрузки
                    $row.removeClass('updating');
                    $('.cart-collaterals').removeClass('loading');

                    // Проверяем, нужно ли обновлять блок доставки
                    const $shippingMethods = $('.cart-collaterals .woocommerce-shipping-methods');
                    const $shippingCalculator = $('.cart-collaterals .shipping-calculator-form');
                    const $deliveryAddress = $('#kv-delivery-address');

                    // Обновляем только если методы доставки отсутствуют или поле адреса пропало
                    if ($shippingMethods.length === 0 || $deliveryAddress.length === 0) {
                        // Показываем индикатор загрузки снова
                        $('.cart-collaterals').addClass('loading');

                        setTimeout(function () {
                            // Запрашиваем методы доставки через AJAX
                            $.ajax({
                                url: ajaxUrl,
                                type: 'POST',
                                data: {
                                    action: 'get_shipping_methods',
                                    security: securityNonce
                                },
                                success: function (shippingResponse) {
                                    if (shippingResponse.success) {
                                        $('.cart-collaterals').html('<h3>Доставка</h3>' + shippingResponse.data);
                                        // Удаляем лишний текст "Доставка"
                                        removeShippingText();
                                        // Проверяем отображение поля адреса доставки
                                        setTimeout(toggleDeliveryAddress, 100);
                                    }
                                    $('.cart-collaterals').removeClass('loading');
                                },
                                error: function () {
                                    $('.cart-collaterals').removeClass('loading');
                                }
                            });
                        }, 300);
                    } else {
                        // Просто проверяем отображение поля адреса
                        setTimeout(toggleDeliveryAddress, 100);
                    }

                    // Триггерим событие обновления корзины
                    $(document.body).trigger('updated_wc_div');
                } else {
                    console.error('Server error:', response.data);
                    alert('Ошибка обновления корзины: ' + (response.data || 'Неизвестная ошибка'));
                    $row.removeClass('updating');
                    $('.cart-collaterals').removeClass('loading');
                }
            },
            error: function (xhr, status, error) {
                console.error('AJAX error:', xhr.responseText, status, error);
                alert('Ошибка соединения: ' + error);
                $row.removeClass('updating');
                $('.cart-collaterals').removeClass('loading');
            }
        });
    });

    // Дебаунс для предотвращения множественных запросов
    let updateTimeout;
    $(document).on('input', '.kv-cart-checkout input[name^="cart["][name$="[qty]"]', function () {
        const $input = $(this);

        clearTimeout(updateTimeout);
        updateTimeout = setTimeout(function () {
            $input.trigger('change');
        }, 500);
    });
});



// Функция для удаления лишнего текста "Доставка"
function removeShippingText() {
    jQuery('.cart-collaterals').contents().filter(function () {
        return this.nodeType === 3 && /^\s*Доставка\s*$/.test(this.textContent);
    }).remove();
}

// Функция для очистки названий товаров от суффиксов единиц измерения
function cleanProductNames() {
    jQuery('.product-name a, .product-name').each(function () {
        var $element = jQuery(this);
        var originalText = $element.text();
        // Убираем различные варианты суффиксов
        var cleanText = originalText.replace(/\s*[-–—]\s*(шт\.?|кг\.?|штук|килограмм)\s*$/gi, '');

        if (originalText !== cleanText) {
            $element.text(cleanText);
        }
    });
}

// Вызываем очистку названий при загрузке и после AJAX обновлений
jQuery(document).ready(function ($) {
    cleanProductNames();

    // Очистка после AJAX обновлений корзины
    $(document).on('updated_wc_div', function () {
        setTimeout(cleanProductNames, 100);
    });

    // Обработка формы оформления заказа
    $('#kv-checkout-form').on('submit', function (e) {
        e.preventDefault();

        const form = $(this);
        const submitBtn = form.find('.kv-order-form__submit');
        const originalText = submitBtn.text();

        // Проверяем заполненность полей
        const name = $('input[name="customer_name"]').val().trim();
        const email = $('input[name="customer_email"]').val().trim();
        const phone = $('input[name="customer_phone"]').val().trim();

        if (!name || !email || !phone) {
            alert('Пожалуйста, заполните все поля');
            return;
        }

        // Проверяем email
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            alert('Пожалуйста, введите корректный email');
            return;
        }

        // Показываем индикатор загрузки
        submitBtn.text('Оформляем...').prop('disabled', true);

        // Отправляем данные на сервер
        $.ajax({
            url: kv_ajax_params.ajax_url,
            type: 'POST',
            data: {
                action: 'kv_process_checkout', // Возвращаем основную функцию
                customer_name: name,
                customer_email: email,
                customer_phone: phone,
                delivery_address: $('#delivery_address').val(),
                security: kv_ajax_params.nonce
            },
            success: function (response) {
                // Проверяем структуру ответа
                if (typeof response === 'object' && response !== null) {
                    if (response.success === true) {

                        // Проверяем наличие URL для редиректа
                        if (response.data && response.data.redirect_url) {
                            // Перенаправляем на страницу благодарности
                            window.location.href = response.data.redirect_url;
                            return; // Прерываем выполнение
                        } else {
                            // Если нет URL, показываем сообщение
                            const orderMessage = 'Заказ успешно оформлен!';
                            const orderIdMessage = response.data && response.data.order_id ? ' Номер заказа: ' + response.data.order_id : '';
                            const contactMessage = '. Мы свяжемся с вами в ближайшее время.';

                            alert(orderMessage + orderIdMessage + contactMessage);
                        }

                        // Очищаем форму
                        form[0].reset();
                        // Перезагружаем страницу для обновления корзины
                        location.reload();
                    } else {
                        console.error('Ошибка сервера:', response.data);
                        const errorMessage = response.data && typeof response.data === 'string' ? response.data : 'Неизвестная ошибка';
                        alert('Ошибка при оформлении заказа: ' + errorMessage);
                    }
                } else {
                    console.error('Некорректный формат ответа:', response);
                    alert('Ошибка: Некорректный ответ сервера');
                }
            },
            error: function (xhr, status, error) {
                console.error('AJAX ошибка:', xhr.responseText, status, error);
                alert('Ошибка соединения. Попробуйте ещё раз.');
            },
            complete: function () {
                // Возвращаем кнопку в обычное состояние
                submitBtn.text(originalText).prop('disabled', false);
            }
        });
    });
});