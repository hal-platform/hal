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

    app.init();
});
