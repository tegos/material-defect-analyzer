$(document).ready(function () {
    var $table = $('#table_matrix_distance');

    if ($table.length === 0) {
        return;
    }

    var values = [];

    $table.find('td').not('.main_diagonal').each(function () {
        var raw = $(this).find('.content').text().trim();
        var val = parseFloat(raw);
        if (!isNaN(val)) {
            values.push(val);
        }
    });

    if (values.length === 0) {
        return;
    }

    var min = Math.min.apply(null, values);
    var max = Math.max.apply(null, values);
    var range = max - min;

    $table.find('td').each(function () {
        var $content = $(this).find('.content');

        if ($(this).hasClass('main_diagonal')) {
            $(this).css('background-color', '#444');
            $content.css('color', '#fff');
            return;
        }

        var raw = $content.text().trim();
        var val = parseFloat(raw);

        if (isNaN(val)) {
            return;
        }

        var ratio = range === 0 ? 0 : (val - min) / range;
        var hue = Math.round(120 - ratio * 120);
        var color = 'hsl(' + hue + ', 70%, 45%)';

        $(this).css('background-color', color);
        $content.css('color', '#fff');
    });
});
