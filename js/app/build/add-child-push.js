import 'jquery';
import formatter from '../util/time-formatter';
import gitref from '../util/git-reference';
import ReactDOM from 'react-dom';
import 'react';
import './deployments.jsx';

var selectorTarget = '.js-environment-selector',
    tableTarget = 'js-child-deployment';

var $selector,
    $table,
    appID,
    deploymentsComponent,
    deploymentsCacheByEnv = {};

var init = () => {
    $selector = $(selectorTarget);
    $table = $('#' + tableTarget);

    if ($table.length === 1 && $selector.length > 0) {
        appID = $table.data('app-id');

        if (appID) {

            deploymentsComponent = ReactDOM.render(
                <DeploymentsTable />,
                document.getElementById(tableTarget)
            );

            $selector.on('change', handleChange);

            // trigger change manually
            $selector.trigger('change');
        }
    }
};

function handleChange(event) {
    if (!event.target.checked) {
        return;
    }

    var envID = event.target.value;

    // use cache if available
    if (deploymentsCacheByEnv.hasOwnProperty(envID)) {
        console.log(`Loading cached environment: ${envID}`);

        deploymentsComponent.setState( { data: deploymentsCacheByEnv[envID] } );
        return;
    }

    var url = `/api/internal/applications/${appID}/environments/${envID}/status`;
    $.ajax(url, {
        context: { appID, envID }
    })
    .fail(handleError)
    .done(handleSuccess);
}

function handleSuccess(data) {
    // Parse and cache response
    deploymentsCacheByEnv[this.envID] = {
        pools: buildStatusContext(data),
        canPush: data.permission,
        deploymentCount: data.statuses.length
    };

    handleStateChange(deploymentsCacheByEnv[this.envID]);
}

function handleError() {
    // wipe out statuses, set to empty state
    delete deploymentsCacheByEnv[this.envID];

    var err = {
        pools: [],
        canPush: false,
        deploymentCount: -1
    };

    handleStateChange(err);
}

function handleStateChange(state) {
    // update react
    deploymentsComponent.setState({
        data: state
    });

    // re-initialize tablesaw
    $table.removeData();
    $table.table();
}

function buildStatusContext(data) {

    var pools = formatPools(data.view);

    var matchDeployment = (element) => {
        return element.deploymentIDs.indexOf(deploymentID) >= 0;
    };

    for (var status of data.statuses) {

        var deploymentID = status.deployment.id,
            deployment = {
                id: deploymentID,
                pretty: status.deployment['pretty-name'],
                additional: status.deployment['detail']
            },
            build = formatBuild(status.build),
            push = formatPush(status.push);

        var pool = pools.find(matchDeployment);

        // If no valid pool is found, append to the last pool (default)
        // This is either "unpooled" or "no pool" if there is no saved view
        if (pool === undefined) {
            pool = pools[pools.length - 1];
        }

        pool.deployments.push({ deployment, build, push });
    }

    return pools;
}

function formatPools(view) {
    var pools = [];

    if (view === null) {
        pools.push({
            name: 'err-no-view',
            deployments: [],
            deploymentIDs: []
        });

    } else {
        for (var pool of view.pools) {
            pools.push({
                name: pool.name,
                deployments: [],
                deploymentIDs: pool.deployments
            });
        }

        // Append unpooled to end
        pools.push({
            name: 'Unpooled',
            deployments: [],
            deploymentIDs: []
        });
    }

    return pools;
}

function formatBuild(build) {
    if (build === null) {
        return null;
    }

    return {
        status: build.status,
        reference: gitref.format(build.reference, 30),
        referenceType: gitref.determineType(build.reference),
        referenceUrl: build._links.github_reference_page.href,

        commit: build.commit,
        commitUrl: build._links.github_commit_page.href
    };
}

function formatPush(push) {
    if (push === null) {
        return null;
    }

    return {
        id: formatPushId(push.id),
        status: push.status,
        inProgress: push.status === 'Waiting' || push.status === 'Pushing',
        time: createTimeElement(push.created),
        user: push._links.user ? push._links.user.title : null,
        url: push._links.page.href
    };
}

function formatPushId(pushId) {
    var regex = /^p[a-zA-Z0-9]{1}.[a-zA-Z0-9]{7}$/i,
        match = null;

    match = regex.exec(pushId);
    if (match !== null && match.length == 1) {
        return match.pop().slice(6);
    }

    return pushId.slice(0, 10);
}

function createTimeElement(time) {
    var formatted = formatter.formatTime(time);
    return '<time datetime="' + time + '" title="' + formatted.absolute + '">' + formatted.relative + '</time>';
}

export default init;
