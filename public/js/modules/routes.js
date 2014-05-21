define(['crossroads'], function(crossroads) {
    var routes;
    return routes = {
        init: function() {
            this.router = crossroads.create();
            this.addRoutes(this.router);
            return this.router;
        },
        addRoutes: function(router) {
            var _this = this;

            // route definitions
            router.addRoute('/admin/repositories', function() {
                return _this.addRepository();
            });
            router.addRoute('/r/{repository}/sync', function() {
                return _this.syncRepository();
            });
            router.addRoute('/r/{repository}/:throwaway:/:page:', function() {
                return _this.deployRepository();
            });
            router.addRoute('/admin/environments', function() {
                return _this.orderEnvironments();
            });
        },

        // route module endpoints
        addRepository: function() {
            return require(['modules/add-repository'], function(module) {
                module.users.attach();
                module.repos.attach();
            });
        },
        deployRepository: function() {
            return require(['modules/deploy-repository'], function(module) {
                module.init();
            });
        },
        syncRepository: function() {
            return require(['modules/sync-repository'], function(module) {
                module.init();
            });
        },
        orderEnvironments: function() {
            return require(['modules/order-environments'], function(module) {
                module.init();
            });
        }
    };
});