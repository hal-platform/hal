define(['crossroads'], function(crossroads) {
    return {
        init: function() {
            this.router = crossroads.create();
            this.addRoutes(this.router);
            return this.router;
        },
        addRoutes: function(router) {
            var _this = this;

            // route definitions
            router.addRoute('/repositories/add', function() {
                return _this.addRepository();
            });
            router.addRoute('/r/{repository}/sync', function() {
                return _this.syncRepository();
            });
            router.addRoute('/environments/reorder', function() {
                return _this.orderEnvironments();
            });
            router.addRoute('/repositories/:throwaway:/deployments', function() {
                return _this.addDeployments();
            });
            router.addRoute('/repositories/:id:/status', function() {
                return _this.updateBuilds();
            });
        },

        // route module endpoints
        addRepository: function() {
            return require(['modules/add-repository'], function(module) {
                module.users.attach();
                module.repos.attach();
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
        },
        addDeployments: function() {
            return require(['modules/add-deployments'], function(module) {
                module.init();
            });
        },
        updateBuilds: function() {
            return require(['modules/update-builds'], function(module) {
                module.init();
            });
        }
    };
});
