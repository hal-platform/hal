require.config({
    shim: {
        underscore: {
            exports: '_'
        }
    },
    paths: {
        jquery: "vendor/jquery-2.0.3.min",
        underscore: "vendor/underscore.min",
        crossroads: "vendor/crossroads.min",
        signals: "vendor/signals.min"
    }
});

define(['modules/routes', 'underscore'], function(routes, _) {
    var app = {
        init: function() {
            var router = routes.init();
            router.parse(this.getPath());
        },
        getPath: function() {
            var a, path;

            a = document.createElement('a');
            a.href = location.href;
            path = a.pathname;

            if (_.first(path) !== "/") {
                path = "/" + path;
            }

            return path;
        }
    };

    app.init();
});

// define(['jquery'], function($) {
//     // toggle secondary nav
//     $('.toggled-nav').hide();
//     $('.toggle-nav').on('click', function() {
//         $('.toggled-nav').slideToggle('fast');
//         return false;
//     });
// });
