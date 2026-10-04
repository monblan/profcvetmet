jQuery(document).ready(function($) {
    var form = $('form[enctype="multipart/form-data"]');
    var loader = $('.kv-loader-wrap');

    form.on('submit', function() {
        loader.show();
    });

    // Скрываем индикатор после загрузки страницы (на случай возврата с результатом)
    $(window).on('load', function() {
        loader.hide();
    });
});