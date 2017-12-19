import 'jquery';

import { formatTime, formatDuration, calculateDuration } from '../util/time-formatter';
import { initEventLogAdder, checkEventLogs, updateEventLogTable } from './event-log-adder';
import { updateFavicon } from './job-favicon-updater';

const POLL_INTERVAL = 5;
const RUNNING_JOB_STATUSES = ['pending', 'running', 'deploying'];

const PENDING_CLASS = 'status-icon--warning';
const THINKING_CLASS = 'status-icon--thinking';
const SUCCESS_CLASS = 'status-icon--success';
const FAILURE_CLASS = 'status-icon--error';

var releaseTarget = '[data-release]';

// "table" for global push table
// "grid" for global push grid
// "push" for individual push status page

function initReleasePage() {
    let target = releaseTarget;

    initJobUpdater(target, startReleasePageUpdateTimer);
    initEventLogAdder();
}

function initReleaseTable() {
    let target = releaseTarget;
    initJobUpdater(target, startReleaseTableUpdateTimer);
}

function initReleaseGrid() {
    let target = releaseTarget;
    initJobUpdater(target, startReleaseGridUpdateTimer);
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
function startReleasePageUpdateTimer($elem) {
    window.setTimeout(function() {

        checkReleaseStatus($elem, (data) => {
            updateRelease(data, $elem);
            updateFavicon(data.status);
            updateEventLogTable(data.status);

            if (RUNNING_JOB_STATUSES.includes(data.status)) {
                startReleasePageUpdateTimer($elem);
            }
        });

        checkEventLogs($elem);

    }, POLL_INTERVAL * 1000);
}

function startReleaseTableUpdateTimer($elem) {
    window.setTimeout(function() {

        checkReleaseStatus($elem, (data) => {
            if (RUNNING_JOB_STATUSES.includes(data.status)) {
                startReleaseTableUpdateTimer($elem);
            }
        });

        checkEventLogs($elem);

    }, POLL_INTERVAL * 1000);
}

function startReleaseGridUpdateTimer($elem) {
    window.setTimeout(function() {

        checkReleaseStatus($elem, (data) => {
            updateGrid($elem);

            if (RUNNING_JOB_STATUSES.includes(data.status)) {
                startReleaseGridUpdateTimer($elem);
            }
        });

        checkEventLogs($elem);

    }, POLL_INTERVAL * 1000);
}

// Requires these properties:
// - id
// - status
// - start
// - end
// - ? _links.start_release_page.href
function checkReleaseStatus($elem, updater) {
    let id = $elem.data('release'),
        endpoint = generateURL(id);

    $.getJSON(endpoint, function(data) {
        let currentStatus = data.status;
        $elem.data('status', currentStatus); // protip: dom is not updated

        if (currentStatus == 'success') {
            $elem
                .removeClass(THINKING_CLASS)
                .addClass(SUCCESS_CLASS);

        } else if (currentStatus == 'failure') {
            $elem
                .removeClass(THINKING_CLASS)
                .addClass(FAILURE_CLASS);
        }

        updater(data);
    });
}

function updateRelease($elem, data) {
    let $container = $elem.closest('ul');

    $elem.text($elem.data('status'));

    if (data.start) {
        let $start = $container.children('.js-release-start');
        if ($start.length > 0 && $start.children('time').length === 0) {
            $start.html(createTimeElement(data.start));
        }
    }

    if (data.end) {
        let $duration = $container.children('.js-release-duration');
        if ($duration.length > 0 && $duration.children('time').length === 0) {
            $duration.html(createTimeDuration(data.start, data.end));
        }
    }
}

function updateGrid($elem) {
    $elem.text($elem.data('status'));
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

function generateURL(releaseID) {
    return '/api/releases/' + releaseID;
}

export { initReleasePage, initReleaseTable, initReleaseGrid };
