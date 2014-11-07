define(
    ['require', 'crossroads', 'controller/_routes', 'modules/terminal', 'modules/nofunzone', 'underscore'],
    function(require, crossroads, routes, terminal, nofunzone, _) {
        return {
            init: function() {
                this.runRouter();
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
            },
            attachRoutes: function(router, routes) {
                routes.forEach(function(route) {
                    router.addRoute(route.url, function() {
                        var controllerModule = "controller/" + route.controller;

                        if (!route.hasOwnProperty('component')) {
                            return require([controllerModule], function(controller) {});
                        }

                        require(["component/" + route.component], function(component) {
                            return require([controllerModule], function(controller) {});
                        });
                    });
                });
            },
            runRouter: function() {
                var router = crossroads.create();

                this.attachRoutes(router, routes);
                router.parse(this.getPath());
            }
        };
    }
);
