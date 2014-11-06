define(['modules/routes', 'modules/terminal', 'modules/nofunzone', 'underscore'], function(routes, terminal, nofunzone, _) {
    var date = new Date();
    var app = {
        init: function() {
            var router = routes.init();
            router.parse(this.getPath());

            terminal.init();
            nofunzone.init();
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
            underscore: {
                exports: '_'
            }
          },
        paths: {
            crossroads: 'vendor/crossroads.min',
            jquery: 'vendor/jquery-2.min',
            jquerySortable: 'vendor/jquery.sortable.min',
            nunjucks: 'vendor/nunjucks.min',
            signals: 'vendor/signals.min',
            tablesaw: 'vendor/tablesaw.min',
            underscore: 'vendor/underscore.min'
        },
        urlArgs: "" + date.getFullYear() + date.getMonth() + date.getDate()
    });

    app.init();
});
