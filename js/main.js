define(['modules/routes', 'modules/terminal', 'underscore'], function(routes, terminal, _) {
    var app = {
        init: function() {
            var router = routes.init();
            router.parse(this.getPath());

            terminal.init();
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

    require.config({
        shim: {
            handlebars: {
                exports: 'Handlebars'
            },
            underscore: {
                exports: '_'
            }
          },
        paths: {
            crossroads: 'vendor/crossroads.min',
            handlebars: 'vendor/handlebars.min',
            jquery: 'vendor/jquery-2.min',
            jquerySortable: 'vendor/jquery.sortable.min',
            signals: 'vendor/signals.min',
            tablesaw: 'vendor/tablesaw.min',
            underscore: 'vendor/underscore.min'
        }
    });

    app.init();
});
