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
            router.addRoute('/environments/reorder', function() {
                return _this.orderEnvironments();
            });
            router.addRoute('/repositories/:throwaway:/deployments', function() {
                return _this.addDeployments();
            });

            router.addRoute('/repositories/:id:/status', function() {
                return _this.updateRepositoryStatus();
            });
            router.addRoute('/build/:id:', function() {
                return _this.updateBuild();
            });
            router.addRoute('/push/:id:', function() {
                return _this.updatePush();
            });
        },

        // route module endpoints
        addRepository: function() {
            return require(['modules/add-repository'], function(module) {
                module.users.attach();
                module.repos.attach();
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

        updateRepositoryStatus: function() {
            return require(['modules/update-builds', 'modules/update-pushes'], function(module, module2) {
                module.init();

                module2.mode = 'grid';
                module2.init();
            });
        },
        updateBuild: function() {
            return require(['modules/update-builds'], function(module) {
                module.mode = 'build';
                module.init();
            });
        },

        updatePush: function() {
            return require(['modules/update-pushes'], function(module) {
                module.mode = 'push';
                module.init();
            });
        }
    };
});
