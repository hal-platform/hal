module.exports = {

    // core
    "queue": function() {
        require('./app/queue/queue').init();
    },

    // forms
    "application.add": function() {
        require('./app/form/add-application').repos.attach();
    },

    "deployment.add": function() {
        require('./app/form/add-deployments').init();
    },

    "pool.add": function() {
        require('./app/form/add-pool').init();
    },

    // jobs - start
    "build.start": function() {
        require('./app/start-build').init();
    },

    "push.start": function() {
        require('./app/start-push').init();
    },

    // jobs - info
    "build.info": function() {
        require('./app/event-log').init();

        var buildUpdater = require('./app/status/update-builds');
        buildUpdater.mode = 'build';
        buildUpdater.init();
    },

    "push.info": function() {
        require('./app/event-log').init();

        var pushUpdater = require('./app/status/update-pushes');
        pushUpdater.mode = 'push';
        pushUpdater.init();
    },

    // jobs - status
    "status": function() {
        require('./app/app-status-overload').init();
        require('./app/app-status-pool').init();
        require('./app/status/update-builds').init();

        var pushUpdater = require('./app/status/update-pushes');
        pushUpdater.mode = 'grid';
        pushUpdater.init();
    },

    "job.updater": function() {
        require('./app/status/update-builds').init();
        require('./app/status/update-pushes').init();
    },

    // Kraken
    "kraken.property": function() {
        require('./app/kraken/form-add-property').init();
    }
};
