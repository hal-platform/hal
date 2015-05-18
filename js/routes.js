exports.routes = [
    {
        url: "/environments/reorder",
        loader: function() {
            var orderEnvironments = require('./app/form/order-environments').module;
            orderEnvironments.init();
        }
    },

    {
        url: "/repositories/add",
        loader: function() {
            var addRepository = require('./app/form/add-repository').module;
            addRepository.repos.attach();
        }
    },
    {
        url: "/repositories/{id}/deployments",
        loader: function() {
            var addDeployment = require('./app/form/add-deployments').module;
            addDeployment.init();
        }
    },

    {
        url: "/admin/server-status",
        loader: function() {
            var serverStatus = require('./app/server-status').module;
            serverStatus.init();
        }
    },
    {
        url: "/superadmin/dangerzone",
        loader: function() {
            var dangerZone = require('./app/util/dangerzone').module;
            dangerZone.init();
        }
    },

    {
        url: "/repositories/{id}/build",
        loader: function() {
            var buildCreator = require('./app/start-build').module;
            buildCreator.init();
        }
    },
    {
        url: "/builds/{id}/push",
        loader: function() {
            var pushCreator = require('./app/start-push').module;
            pushCreator.init();
        }
    },

    {
        url: "/",
        loader: function() {
            var dashboard = require('./app/dashboard').module;
            var queue = require('./app/queue/queue').module;

            dashboard.init();
            queue.init();
        }
    },
    {
        url: "/queue",
        loader: function() {
            var queue = require('./app/queue/queue').module;

            queue.init();
        }
    },

    {
        url: "/repositories/{id}/status",
        loader: function() {
            var buildUpdater = require('./app/update-builds').module;
            var pushUpdater = require('./app/update-pushes').module;
            var overloader = require('./app/repository-status-overload').module;

            buildUpdater.init();

            pushUpdater.mode = 'grid';
            pushUpdater.init();

            overloader.init();
        }
    },
    {
        url: "/builds/{id}",
        loader: function() {
            var buildUpdater = require('./app/update-builds').module;
            var eventLog = require('./app/event-log').module;

            buildUpdater.mode = 'build';
            buildUpdater.init();

            eventLog.init();
        }
    },
    {
        url: "/pushes/{id}",
        loader: function() {
            var pushUpdater = require('./app/update-pushes').module;
            var eventLog = require('./app/event-log').module;

            pushUpdater.mode = 'push';
            pushUpdater.init();

            eventLog.init();
        }
    },
    {
        url: "/repositories/{id}/builds/:page*:",
        loader: function() {
            var buildUpdater = require('./app/update-builds').module;
            var pushUpdater = require('./app/update-pushes').module;

            buildUpdater.init();
            pushUpdater.init();
        }
    },
    {
        url: "/repositories/{id}/pushes/:page*:",
        loader: function() {
            var buildUpdater = require('./app/update-builds').module;
            var pushUpdater = require('./app/update-pushes').module;

            buildUpdater.init();
            pushUpdater.init();
        }
    },

    // Kraken
    {
        url: "/kraken/applications/{id}/environments/{env}/add",
        loader: function() {
            var propertyForm = require('./app/kraken/form-add-property').module;
            propertyForm.init();
        }
    }
];
