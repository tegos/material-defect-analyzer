(function () {

    var body = document.body;
    var sidebar = document.getElementById('sidebar');

    body.classList.add('is-loading');
    window.addEventListener('load', function () {
        setTimeout(function () {
            body.classList.remove('is-loading');
        }, 100);
    });

    document.addEventListener('click', function (e) {
        var a = e.target.closest('a');
        if (!a) return;
        var href = a.getAttribute('href');
        if (!href || href.charAt(0) !== '#') return;
        var target = document.querySelector(href);
        if (!target) return;
        e.preventDefault();
        target.scrollIntoView({ behavior: 'smooth' });
    });

    if (sidebar) {
        var sidebarLinks = Array.from(sidebar.querySelectorAll('a'));

        sidebarLinks.forEach(function (link) {
            link.addEventListener('click', function () {
                var href = link.getAttribute('href');
                if (!href || href.charAt(0) !== '#') return;
                sidebarLinks.forEach(function (l) { l.classList.remove('active'); });
                link.classList.add('active', 'active-locked');
            });

            var href = link.getAttribute('href');
            if (!href || href.charAt(0) !== '#') return;
            var sectionId = href.replace('/', '');
            var section = document.querySelector(sectionId);
            if (!section) return;

            section.classList.add('inactive');

            new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (!entry.isIntersecting) return;
                    section.classList.remove('inactive');
                    var locked = sidebarLinks.some(function (l) {
                        return l.classList.contains('active-locked');
                    });
                    if (!locked) {
                        sidebarLinks.forEach(function (l) { l.classList.remove('active'); });
                        link.classList.add('active');
                    } else if (link.classList.contains('active-locked')) {
                        link.classList.remove('active-locked');
                    }
                });
            }, { rootMargin: '-20% 0px -20% 0px' }).observe(section);
        });
    }

    document.querySelectorAll('.spotlights > section').forEach(function (el) {
        var image = el.querySelector('.image');
        var img = image ? image.querySelector('img') : null;
        if (image && img) {
            image.style.backgroundImage = 'url(' + img.getAttribute('src') + ')';
            var pos = img.dataset.position;
            if (pos) image.style.backgroundPosition = pos;
            img.style.display = 'none';
        }
        el.classList.add('inactive');
        new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) el.classList.remove('inactive');
            });
        }, { rootMargin: '-10% 0px -10% 0px' }).observe(el);
    });

    document.querySelectorAll('.features').forEach(function (el) {
        el.classList.add('inactive');
        new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) el.classList.remove('inactive');
            });
        }, { rootMargin: '-20% 0px -20% 0px' }).observe(el);
    });

}());
