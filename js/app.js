var crossroads = require('crossroads');
var routes = require('./routes').routes;
var _ = require('underscore');

var terminal = require('./app/util/terminal').module;
var nofunzone = require('./app/util/nofunzone').module;
var reltime = require('./app/util/relative-time').module;

var app = {
    initialize: function() {
        var router = crossroads.create();

        this.attachRoutes(router, routes);
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
    },
    attachRoutes: function(router, routes) {
        routes.forEach(function(route) {
            router.addRoute(route.url, route.loader);
        });
    }
};

app.initialize();
terminal.init();
nofunzone.init();
reltime.init();
