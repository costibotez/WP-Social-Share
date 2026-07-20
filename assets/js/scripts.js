;(function ($) {
    $(document).ready(function () {
        $('.color-field').wpColorPicker();

        var defaultColors = $('input[name="toptal_ss_settings[default_colors]"]');
        var colorRows = defaultColors.closest('tr').nextAll();

        function toggleColorRows(immediate) {
            if (defaultColors.is(':checked')) {
                immediate ? colorRows.hide() : colorRows.fadeOut('slow');
            } else {
                immediate ? colorRows.show() : colorRows.fadeIn('slow');
            }
        }

        toggleColorRows(true);
        defaultColors.on('change', function () {
            toggleColorRows(false);
        });
    });
})(jQuery);
