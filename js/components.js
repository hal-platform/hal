import SearchBuild from './app/build/search';
import EventLogLoader from './app/event-log';

import SearchApplications from './app/apps-filter';
import DynamicTargetsForm from './app/form/dynamic-targets';

module.exports = {

    // core
    "queue": function() {
        require('./app/queue/queue').init();
    },

    // forms
    "target.add": function() {
        DynamicTargetsForm();
    },

    // jobs - start
    "build.start": () => {
        SearchBuild();
        require('./app/start-push').init();
    },

    "push.start": function() {
        require('./app/start-push').init();
    },

    // jobs - info
    "build.info": function() {
        EventLogLoader();

        var buildUpdater = require('./app/status/update-builds');
        buildUpdater.mode = 'build';
        buildUpdater.init();
    },

    "push.info": function() {
        EventLogLoader();

        var pushUpdater = require('./app/status/update-pushes');
        pushUpdater.mode = 'push';
        pushUpdater.init();
    },

    // jobs - status
    "status": function() {
        require('./app/status/update-builds').init();

        var pushUpdater = require('./app/status/update-pushes');
        pushUpdater.mode = 'grid';
        pushUpdater.init();
    },

    "job.updater": function() {
        require('./app/status/update-builds').init();
        require('./app/status/update-pushes').init();
    },

    "favorites": function() {
        require('./app/form/add-favorites').init();
    },
    "collapsible": function() {
        require('./app/collapsible-table').init();
    },

    "apps.filter": function() {
        SearchApplications();
    },

    "app.permissions.multi": function() {
        require('./app/form/app-permissions-multi').init();
    }

};
