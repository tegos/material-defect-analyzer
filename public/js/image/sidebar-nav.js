$(function () {
    var $sections = $('#intro .inner section h2');
    var $navList = $('#sidebar nav ul');

    if ($sections.length === 0 || $navList.length === 0) {
        return;
    }

    // Assign ids and build nav links
    $sections.each(function (i) {
        var id = 'section-' + i;
        $(this).attr('id', id);
        $navList.append(
            $('<li>').append(
                $('<a>').attr('href', '#' + id).text($(this).text())
            )
        );
    });

    var $navLinks = $navList.find('a');

    // Smooth scroll on click
    $navLinks.on('click', function (e) {
        e.preventDefault();
        var target = $(this).attr('href');
        $('html, body').animate(
            { scrollTop: $(target).offset().top - 20 },
            400
        );
    });

    // Scrollspy
    function updateActive() {
        var scrollTop = $(window).scrollTop();
        var midpoint = scrollTop + $(window).height() / 2;
        var activeIndex = 0;

        $sections.each(function (i) {
            if ($(this).offset().top <= midpoint) {
                activeIndex = i;
            }
        });

        $navLinks.removeClass('active');
        $navLinks.eq(activeIndex).addClass('active');
    }

    $(window).on('scroll.sidebarNav', updateActive);
    updateActive();
});
