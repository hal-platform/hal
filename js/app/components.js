import { initTabSelector } from './util/tab-selector';
import { initGitHubSearch } from './job/github-search-for-build';
import { initEventLogLoader } from './job/event-log-loader';

import { initApplicationForm } from './form/application-form';
import { initCredentialForm } from './form/credential-form';
import { initTargetForm } from './form/target-form';
import { initUserForm } from './form/user-form';
import { initMetadataForm } from './form/metadata-form';

import { initSelectAllCheckboxes } from './form/select-all-checkbox';
import { initApplicationPermissions } from './form/manage-application-permissions';

import { initFavoriteApplications } from './applications/manage-favorite-applications';
import { initSearchableApplications } from './applications/searchable-applications';
import { initApplicationTable } from './applications/collapsible-applications';

import { initQueue } from './queue/queue';
import { initBuildPage, initBuildTable } from './job/update-inflight-builds';
import { initReleasePage, initReleaseTable, initReleaseGrid } from './job/update-inflight-deployments';

let appComponents = {
    // forms
    add_target_form: () => {
        initTargetForm();
    },
    add_application_form: () => {
        initApplicationForm();
    },
    add_credential_form: () => {
        initCredentialForm();
    },
    add_user_form: () => {
        initUserForm();
    },

    // jobs - start
    start_build: () => {
        initTabSelector();
        initGitHubSearch();
        initSelectAllCheckboxes();
        initMetadataForm();
    },
    start_deployment: () => {
        initSelectAllCheckboxes();
        initMetadataForm();
    },

    // jobs - info/updating
    build_info: () => {
        initEventLogLoader();
        initBuildPage();
    },

    release_info: () => {
        initEventLogLoader();
        initReleasePage();
    },

    job_queue: () => {
        initQueue();
    },
    job_table_updater: () => {
        initBuildTable();
        initReleaseTable();
    },

    // applications
    application_dashboard: () => {
        initBuildTable();
        initReleaseGrid();
    },

    applications_list: () => {
        initFavoriteApplications();
        initApplicationTable();
        initSearchableApplications();
    },

    application_permissions: () => {
        initApplicationPermissions();
    }
};

function runComponent(componentName) {
    if (appComponents.hasOwnProperty(componentName)) {
        appComponents[componentName]();
    } else {
        console.log(`Component not found: ${componentName}`);
    }
}

function runComponents(dataAttribute) {
    var requested = document.querySelector('head').getAttribute(dataAttribute);
    if (requested === 'undefined' || requested === null) {
        return;
    }

    // Load components
    requested.split(' ').map(runComponent);
}

export { appComponents, runComponents };
