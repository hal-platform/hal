import 'jquery';

import { formatTime, formatDuration, calculateDuration } from '../util/time-formatter';
import { initEventLogAdder, checkEventLogs, updateEventLogTable } from './event-log-adder';
import { updateFavicon } from './job-favicon-updater';

const POLL_INTERVAL = 5;
const RUNNING_JOB_STATUSES = ['pending', 'running'];

const PENDING_CLASS = 'status-icon--warning';
const THINKING_CLASS = 'status-icon--thinking';
const SUCCESS_CLASS = 'status-icon--success';
const FAILURE_CLASS = 'status-icon--error';

var buildTarget = '[data-build]';

function initBuildPage() {
    let target = buildTarget;

    initJobUpdater(target, startBuildPageUpdateTimer);
    initEventLogAdder();
}

function initBuildTable() {
    let target = buildTarget;
    initJobUpdater(target, startBuildTableUpdateTimer);
}

// @todo move out to common "job updater" module?
function initJobUpdater(target, timerUpdater) {
    $(target)
        .each(function(index, item) {
            let $item = $(item),
                currentStatus = $item.data('status').trim();

            if (RUNNING_JOB_STATUSES.includes(currentStatus)) {
                $item
                    .removeClass(PENDING_CLASS)
                    .addClass(THINKING_CLASS);

                timerUpdater($item);
            }
        });
}

// @todo move out to common "job updater" module?
function startBuildPageUpdateTimer($elem) {
    window.setTimeout(function() {

        checkBuildStatus($elem, (data) => {
            updateBuild(data, $elem);
            updateFavicon(data.status);
            updateEventLogTable(data.status);

            if (RUNNING_JOB_STATUSES.includes(data.status)) {
                startBuildPageUpdateTimer($elem);
            }
        });

        checkEventLogs($elem);

    }, POLL_INTERVAL * 1000);
}

function startBuildTableUpdateTimer($elem) {
    window.setTimeout(function() {

        checkBuildStatus($elem, (data) => {
            updateTable(data, $elem);

            if (RUNNING_JOB_STATUSES.includes(data.status)) {
                startBuildTableUpdateTimer($elem);
            }
        });

    }, POLL_INTERVAL * 1000);
}

// Requires these properties:
// - id
// - status
// - start
// - end
// - ? _links.start_release_page.href
function checkBuildStatus($elem, updater) {
    let id = $elem.data('build'),
        endpoint = generateURL(id);

    $.getJSON(endpoint, function(data) {
        let currentStatus = data.status;
        $elem.data('status', currentStatus); // protip: dom is not updated

        if (RUNNING_JOB_STATUSES.includes(currentStatus)) {
            // If still pending, fire up a countdown for the next callback in the chain.

        } else if (currentStatus == 'success') {
            $elem
                .removeClass(THINKING_CLASS)
                .addClass(SUCCESS_CLASS);

        } else {
            $elem
                .removeClass(THINKING_CLASS)
                .addClass(FAILURE_CLASS);
        }

        updater(data);
    });
}

function updateBuild(data, $elem) {
    let $container = $elem.closest('ul');

    $elem.text($elem.data('status'));

    updatePushLink(data.status, data._links);

    if (data.start) {
        let $start = $container.children('.js-build-start');
        if ($start.length > 0 && $start.children('time').length === 0) {
            $start.html(createTimeElement(data.start));
        }
    }

    if (data.end) {
        let $duration = $container.children('.js-build-duration');
        if ($duration.length > 0 && $duration.children('time').length === 0) {
            $duration.html(createTimeDuration(data.start, data.end));
        }
    }
}

function updatePushLink(jobStatus, links) {
    if (jobStatus == 'success') {
        // Add push link if present
        if (links.start_release_page && links.start_release_page.href) {
            $('.js-build-push')
                .html(`<a class="btn btn--action" href="${links.start_release_page.href}">Deploy Build</a>`);
        }

        // Replace success messaging
        let $hdr = $('[data-success]');
        if ($hdr.length == 1) {
            $hdr.text($hdr.data('success'));
        }

    } else if (jobStatus == 'failure') {
        // Replace success messaging
        let $hdr = $('[data-failure]');
        if ($hdr.length == 1) {
            $hdr.text($hdr.data('failure'));
        }
    }
}

function updateTable(data, $elem) {
    let $container = $elem.closest('tr'),
        links = data._links;

    if (data.status == 'success') {
        // Add push link if present
        if (links.start_release_page && links.start_release_page.href) {
            $container
                .children('.js-build-push')
                .html(`<a class="btn btn--tiny" href="${links.start_release_page.href}">Deploy</a>`);
        }
    }
}

function createTimeElement(time) {
    var formatted = formatTime(time);
    return '<time datetime="' + time + '" title="' + formatted.absolute + '">' + formatted.relative + '</time>';
}
function createTimeDuration(start, end) {
    var duration = calculateDuration(start, end),
        formatted = formatDuration(duration);
    return '<time datetime="' + duration + '" title="' + formatted.absolute + '">' + formatted.relative + '</time>';
}

function generateURL(buildID) {
    return '/api/builds/' + buildID;
}

export { initBuildPage, initBuildTable };
