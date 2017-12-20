import 'jquery';
import {
    stopThinking,
    addBuildJob,
    updateBuildJob,
    addReleaseJob,
    updateReleaseJob
} from '../job/job-table-updater';

const QUEUE_TARGET = '#js-queue tbody';
const JOB_TARGET = '[data-release], [data-build]';
const POLL_INTERVAL = 10;

var pollingTimer = null,
    lastRead = null,
    jobs = {}, // the list of the jobs we need to update
    $queue = null;

var initQueue = function() {
    // global state: lastRead, $queue

    lastRead = getUTCTime();
    $queue = $(QUEUE_TARGET);
    if ($queue.length) {
        togglePolling();

        $(JOB_TARGET).each(saveJob);
    }
};

function saveJob(index, item) {
    // global state: jobs

    var $item = $(item);
    var status = $item.data('status').trim();

    var uniqueID = $item.data('push');
    if (typeof uniqueID === 'undefined') {
        uniqueID = $item.data('build');
    }

    jobs[uniqueID] = status;
}

function refreshQueue() {
    // get new jobs
    retrieveNewJobs();

    // update jobs loaded on the page
    retrieveJobUpdates();
}

function startRefreshTimer() {
    return window.setInterval(function() {
        refreshQueue();
    }, POLL_INTERVAL * 1000);
}

function togglePolling() {
    // global state: pollingTimer

    if (pollingTimer === null) {
        refreshQueue();
        pollingTimer = startRefreshTimer();
    } else {
        clearInterval(pollingTimer);
        pollingTimer = null;

        stopThinking(JOB_TARGET);
    }

    // return the current id of the timer
    return pollingTimer;
}


function addJobs(data) {
    // global state: jobs, $queue

    // Requires these properties:
    // - id
    // - status
    for(var entry in data) {
        var job = data[entry];
        var row;
        var type = determineJobType(job.id);

        if (type == 'build') {
            // only load jobs not already loaded
            if (typeof jobs[job.id] == 'undefined') {
                row = addBuildJob(job);

                $queue.prepend(row);
                jobs[job.id] = job.status;
            }
        } else if (type == 'release') {
            // only load jobs not already loaded
            if (typeof jobs[job.id] == 'undefined') {
                row = addReleaseJob(job);

                $queue.prepend(row);
                jobs[job.id] = job.status;
            }
        }
    }
}

// Requires these properties:
// - count
// - _embedded.jobs

// Add Job requires these properties:
// - _embedded.jobs.[].id
// - _embedded.jobs.[].status

// Add Build Job requires these properties:
// - _embedded.jobs.[].id
// - _embedded.jobs.[].reference.text
// - _embedded.jobs.[].url
// - _embedded.jobs.[].status
// - _embedded.jobs.[].commit.url
// - _embedded.jobs.[]._links.user
// - _embedded.jobs.[]._links.user.title
// - _embedded.jobs.[]._links.environment.title
// - _embedded.jobs.[]._embedded.application.title
// - _embedded.jobs.[]._embedded.application.url

// Add Release Job requires these properties:
// - _embedded.jobs.[].id
// - _embedded.jobs.[].url
// - _embedded.jobs.[].status
// - _embedded.jobs.[]._links.user
// - _embedded.jobs.[]._links.user.title
// - _embedded.jobs.[]._embedded.build.id
// - _embedded.jobs.[]._embedded.build._links.environment.title
// - _embedded.jobs.[]._embedded.application.title
// - _embedded.jobs.[]._embedded.application.url
// - _embedded.jobs.[]._embedded.deployment._links.server.title
function retrieveNewJobs() {
    // global state: lastRead

    let endpoint = generateURL(lastRead, 'new');

    // retrieve jobs created since last read
    $.getJSON(endpoint, function(data) {
        if (data.count > 0) {
            $('#js-emptyQueue').remove();
            addJobs(data._embedded.jobs.reverse());
        }
    });

    // update last read for next polling call
    lastRead = getUTCTime();
}

// Requires these properties:
// - _embedded.jobs
// - _embedded.jobs.[].id

// Update Build Job requires these properties:
// - _embedded.jobs.[].id
// - _embedded.jobs.[].status

// Update Release Job requires these properties:
// - _embedded.jobs.[].id
// - _embedded.jobs.[].status
function retrieveJobUpdates() {
    // global state: jobs

    // a list of ids to update
    var jobsToUpdate = [];

    // build the list of jobs to update
    for (let id in jobs) {
        let currentStatus = jobs[id];
        if (currentStatus == 'pending' || currentStatus == 'running' || currentStatus == 'deploying') {
            jobsToUpdate.push(id);
        }
    }

    if (jobsToUpdate.length <= 0) {
        return;
    }

    // call api and update job rows
    let endpoint = generateURL(jobsToUpdate.join('+'), 'refresh');

    $.getJSON(endpoint, function(data) {
        for (let entry in data._embedded.jobs) {
            let type = determineJobType(data._embedded.jobs[entry].id);

            if (type == 'build') {
                updateBuildJob(data._embedded.jobs[entry]);
            } else if (type == 'release') {
                updateReleaseJob(data._embedded.jobs[entry]);
            }
        }
    });
}

function generateURL(param, type) {
    if (type === 'new') {
        return '/api/queue?since=' + param;
    } else if (type === 'refresh') {
        return '/api/queue-refresh/' + param;
    }
}

function determineJobType(jobID) {
    var type = jobID.charAt(0).toUpperCase();

    if (type === 'B') {
        return 'build';
    } else if (type === 'R') {
        return 'release';
    }
}

function getUTCTime() {
    var now = new Date();

    var min = now.getUTCMinutes();
    // if in the first half of a minute, reduce minutes by 1
    // this is to make sure we don't miss any jobs
    if (now.getUTCSeconds() < 30 && min > 0) {
        min--;
    }

    var date = now.getUTCFullYear() + '-' +
        ('0' + (now.getUTCMonth()+1)).slice(-2) + '-' +
        ('0' + (now.getUTCDate())).slice(-2);

    var time =
        ('0' + now.getUTCHours()).slice(-2) + ':' +
        ('0' + min).slice(-2) + ':' +
        '00';

    return date + 'T' + time + '-0000';
}

export { initQueue };
