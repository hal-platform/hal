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
        },

        // route module endpoints
        addRepository: function() {
            return require(['modules/add-repository'], function(module) {
                module.users.attach();
                module.repos.attach();
            });
        }
    };
});