import 'jquery';
import tpl from '../../nunjucks/deployment.status.nunj';
import formatter from '../util/time-formatter';
import gitref from '../util/git-reference';

var selectorTarget = '.js-environment-selector',
    tableTarget = '.js-child-deployment';

var $selector,
    $table,
    appID,
    env = {};

var init = () => {
    $selector = $(selectorTarget);
    $table = $(tableTarget);

    if ($table.length === 1 && $selector.length > 0) {
        appID = $table.data('app-id');

        if (appID) {
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
    if (env.hasOwnProperty(envID)) {
        console.log(`Loading cached environment: ${envID}`);
        prepareDeployments(env[envID]);
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
    // Cache response
    env[this.envID] = data;

    prepareDeployments(data);
}

function handleError() {
    // wipe out statuses, set to empty state
    delete env[envID];

    $table
        // Kill previous env display
        .children('.js-autopush-env')
        .remove().end()

        // Show empty display
        .children('.js-autopush-empty')
        .show();
}

function prepareDeployments(data) {
    var context = {
            statuses: buildStatusContext(data),
            canPush: data.permission
        },
        $deployments = $(tpl.render(context));

    $table
        // Kill previous env display
        .children('.js-autopush-env')
        .remove().end()

        // Add deploys
        .append($deployments)

        // Hide empty display
        .children('.js-autopush-empty')
        .hide();

    // re-initialize tablesaw
    $table.removeData();
    $table.table();
}

function buildStatusContext(data) {

    var statuses = [];

    for (var status of data.statuses) {

        var context,
            deployment = {
                id: status.deployment.id,
                pretty: status.deployment['pretty-name'],
                additional: status.deployment['detail'],
            },
            build = null,
            push = null,

            pushStatus = status.push === null ? null : status.push,
            buildStatus = status.build === null ? null : status.build;

        if (pushStatus !== null) {
            push = {
                id: formatPushId(pushStatus.id),
                status: pushStatus.status,
                inProgress: pushStatus.status === 'Waiting' || pushStatus.status === 'Pushing',
                time: createTimeElement(status.push['created']),
                user: status.push._links.user ? status.push._links.user.title : null,
                url: pushStatus._links.page.href
            };
        }

        if (buildStatus !== null) {
            build = {
                status: buildStatus.status,
                reference: gitref.format(buildStatus.reference, 30),
                referenceType: gitref.determineType(buildStatus.reference),
                referenceUrl: buildStatus._links.github_reference_page.href,

                commit: buildStatus.commit,
                commitUrl: buildStatus._links.github_commit_page.href
            };
        }

        statuses.push({ deployment, build, push });
    }

    return statuses;
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
