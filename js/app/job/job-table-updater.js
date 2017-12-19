import 'jquery';
import { formatTime } from '../util/time-formatter';
import { generateIcon } from '../util/icon';
import { determineGitref, determineGitrefType } from '../util/git-reference';
import {
    getUserFromEntity,
    getEnvironmentFromEntity,
    getTargetFromEntity,
    formatBuildID,
    formatReleaseID
} from './job-formatters';

const PENDING_CLASS = 'status-icon--warning';
const THINKING_CLASS = 'status-icon--thinking';
const SUCCESS_CLASS = 'status-icon--success';
const FAILURE_CLASS = 'status-icon--error';

function addBuildJob(build) {
    return renderBuildRow({
        build: {
            id: build.id,
            status: build.status,
            page: build._links.page.href,
            created: build.created,
            username: getUserFromEntity(build)
        },
        reference: {
            name: build.reference,
            page: build._links.github_commit_page.href
        },
        application: {
            name: build._embedded.application.name,
            page: build._embedded.application._links.status_page.href
        },
        environment: {
            name: getEnvironmentFromEntity(build)
        }
    });
}

function addReleaseJob(release) {
    return renderReleaseRow({
        build: {
            id: release._embedded.build.id,
            page: release._embedded.build._links.page.href
        },
        release: {
            id: release.id,
            status: release.status,
            page: release._links.page.href,
            created: release.created,
            username: getUserFromEntity(release)
        },
        application: {
            name: release._embedded.application.name,
            page: release._embedded.application._links.status_page.href
        },
        environment: {
            name: getEnvironmentFromEntity(release),
            target: getTargetFromEntity(release)
        }
    });
}

function updateReleaseJob(job) {
    var $elem = $('[data-release="' + job.id + '"]');
    var currentStatus = job.status;

    if (currentStatus == 'pending' || currentStatus == 'deploying' || currentStatus == 'running') {
        $elem
            .removeClass(PENDING_CLASS)
            .addClass(THINKING_CLASS);

    } else if (currentStatus == 'success') {
        $elem
            .removeClass(THINKING_CLASS)
            .addClass(SUCCESS_CLASS);

    } else {
        $elem
            .removeClass(THINKING_CLASS)
            .addClass(FAILURE_CLASS);
    }
}

function updateBuildJob(job) {
    var $elem = $('[data-build="' + job.id + '"]');
    var currentStatus = job.status;

    if (currentStatus == 'pending' || currentStatus == 'running') {
        $elem
            .removeClass(PENDING_CLASS)
            .addClass(THINKING_CLASS);

    } else if (currentStatus == 'success') {
        $elem
            .removeClass(THINKING_CLASS)
            .addClass(SUCCESS_CLASS);

    } else {
        $elem
            .removeClass(THINKING_CLASS)
            .addClass(FAILURE_CLASS);
    }
}

function stopThinking(jobTarget) {
    $(jobTarget).each(function(index, item) {
        var $elem = $(item);
        if ($elem.hasClass(THINKING_CLASS)) {
            $elem
                .removeClass(THINKING_CLASS)
                .addClass(PENDING_CLASS);
        }
    });
}

function determineStatusStyle(status) {
    if (status == 'success') {
        return SUCCESS_CLASS;

    } else if (status == 'failure') {
        return FAILURE_CLASS;

    } else if (status == 'pending' || status == 'runnning' || status == 'deploying') {
        return THINKING_CLASS;
    }

    return PENDING_CLASS;
}

function renderBuildRow(context) {
    let build_id_short = formatBuildID(context.build.id),
        build_status_class = determineStatusStyle(context.build.status),
        build_time = formatTime(context.build.created),

        reference = determineGitref(context.reference.name, 30),
        github_icon = generateIcon(determineGitrefType(reference));

    let template = `
<tr id="${context.build.id}">
    <td>
        <span class="${build_status_class}" data-build="${context.build.id}">
            Build <a href="${context.build.page}">${build_id_short}</a>
        </span>
    </td>

    <td><a href="${context.application.page}">${context.application.name}</a></td>
    <td>${context.environment.name}</td>

    <td>
        <a href="${context.reference.page}">${reference}</a> ${github_icon}
    </td>
    <td class="table-priority-55">${context.build.username}</td>
    <td class="table-priority-50">
        <time datetime="${context.build.created}" title="${build_time.absolute}">${build_time.relative}</time>
    </td>
</tr>
`;

    return template;
}

function renderReleaseRow(context) {
    let release_id_short = formatReleaseID(context.release.id),
        release_status_class = determineStatusStyle(context.release.status),
        release_time = formatTime(context.release.created),

        build_id_short = formatBuildID(context.build.id);

    let template = `
<tr id="${context.release.id}">
    <td>
        <span class="${release_status_class}" data-release="${context.release.id}">
            Release <a href="${context.release.page}">${release_id_short}</a>
        </span>
    </td>

    <td><a href="${context.application.page}">${context.application.name}</a></td>
    <td>${context.environment.name} → ${context.environment.target}</td>

    <td>
        Build <a href="${context.build.page}">${build_id_short}</a>
    </td>
    <td class="table-priority-55">${context.release.username}</td>
    <td class="table-priority-50">
        <time datetime="${context.release.created}" title="${release_time.absolute}">${release_time.relative}</time>
    </td>
</tr>

`;

    return template;
}

export {
    stopThinking,

    addBuildJob,
    updateBuildJob,

    addReleaseJob,
    updateReleaseJob
};
