import SearchBuild from './app/build/search';
import EventLogLoader from './app/event-log';

import DynamicTargetsForm from './app/form/dynamic-targets';
import DynamicCredentialsForm from './app/form/dynamic-credentials';
import SelectAllCheckboxes from './app/form/select-all-checkbox';
import ManageAppPermissions from './app/form/manage-application-permissions';

import ManageFavoriteApps from './app/applications/manage-favorite-applications';
import SearchApplications from './app/applications/searchable-applications';
import CollapsibleApplications from './app/applications/collapsible-applications';

module.exports = {

    // core
    "queue": function() {
        require('./app/queue/queue').init();
    },

    // forms
    "target.add": function() {
        DynamicTargetsForm();
    },

    // forms
    "credential.add": function() {
        DynamicCredentialsForm();
    },

    // jobs - start
    "build.start": () => {
        SearchBuild();
        SelectAllCheckboxes();
    },

    "push.start": function() {
        SelectAllCheckboxes();
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

    "applications.list": function() {
        ManageFavoriteApps();
        CollapsibleApplications();
        SearchApplications();
    },

    "app.permissions.multi": function() {
        ManageAppPermissions();
    }

};
