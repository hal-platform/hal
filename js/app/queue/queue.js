var $ = require('jquery');
var jobUpdater = require('./job-updater');

module.exports = {
    pollingTimer: null,
    interval: 10,

    // a list of the jobs we need to update
    jobs: {},
    lastRead: null,

    queueTarget: '#js-queue tbody',
    jobTarget: '[data-push], [data-build]',
    $queue: null,

    init: function() {
        this.lastRead = this.getUTCTime();
        this.$queue = $(this.queueTarget);
        if (this.$queue.length) {
            this.togglePolling();

            jobUpdater.init();

            // Need to load initial jobs list
            this.storeInitialJobs();
        }
    },
    refresh: function() {
        // get new jobs
        this.retrieveNewJobs();

        // update jobs loaded on the page
        this.retrieveJobUpdates();
    },
    startRefreshTimer: function() {
        var _this = this;

        return window.setInterval(function() {
            _this.refresh();
        }, _this.interval * 1000);
    },

    generateUrl: function(param, type) {
        if (type === 'new') {
            return '/api/queue?since=' + param;
        } else if (type === 'refresh') {
            return '/api/queue-refresh/' + param;
        }
    },

    togglePolling: function() {
        var _this = this;

        if (this.pollingTimer === null) {
            this.refresh();
            this.pollingTimer = this.startRefreshTimer();
        } else {
            clearInterval(this.pollingTimer);
            this.pollingTimer = null;

            jobUpdater.stopThinking(this.jobTarget);
        }

        // return the current id of the timer
        return this.pollingTimer;
    },

    addJobs: function(data) {
        // Requires these properties:
        // - id
        // - status
        for(var entry in data) {
            var job = data[entry];
            var row;
            var type = this.determineJobType(job.id);

            if (type == 'build') {
                // only load jobs not already loaded
                if (typeof this.jobs[job.id] == 'undefined') {
                    row = jobUpdater.addBuildJob(job);

                    this.$queue.prepend(row);
                    this.jobs[job.id] = job.status;
                }
            } else if (type == 'push') {
                // only load jobs not already loaded
                if (typeof this.jobs[job.id] == 'undefined') {
                    row = jobUpdater.addPushJob(job);

                    this.$queue.prepend(row);
                    this.jobs[job.id] = job.status;
                }
            }
        }
    },

    retrieveNewJobs: function() {
        var _this = this;

        var endpoint = this.generateUrl(this.lastRead, 'new');
        this.lastRead = this.getUTCTime();

        // retrieve jobs created since last read

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
        // - _embedded.jobs.[]._embedded.repository.title
        // - _embedded.jobs.[]._embedded.repository.url

        // Add Push Job requires these properties:
        // - _embedded.jobs.[].id
        // - _embedded.jobs.[].url
        // - _embedded.jobs.[].status
        // - _embedded.jobs.[]._links.user
        // - _embedded.jobs.[]._links.user.title
        // - _embedded.jobs.[]._embedded.build.id
        // - _embedded.jobs.[]._embedded.build._links.environment.title
        // - _embedded.jobs.[]._embedded.repository.title
        // - _embedded.jobs.[]._embedded.repository.url
        // - _embedded.jobs.[]._embedded.deployment._links.server.title

        $.getJSON(endpoint, function(data) {
            if (data.count > 0) {
                $('#js-emptyQueue').remove();
                _this.addJobs(data._embedded.jobs.reverse());
            }
        });
    },
    retrieveJobUpdates: function() {
        var _this = this;

        // a list of ids to update
        var jobsToUpdate = [];

        // build the list of jobs to update
        for (var id in this.jobs) {
            var currentStatus = this.jobs[id];
            if (currentStatus == 'Waiting' || currentStatus == 'Building' || currentStatus == 'Pushing') {
                jobsToUpdate.push(id);
            }
        }

        if (jobsToUpdate.length > 0) {
            // call api and update job rows
            var endpoint = this.generateUrl(jobsToUpdate.join('+'), 'refresh');

            // Requires these properties:
            // - _embedded.jobs
            // - _embedded.jobs.[].id

            // Update Build Job requires these properties:
            // - _embedded.jobs.[].id
            // - _embedded.jobs.[].status

            // Update Push Job requires these properties:
            // - _embedded.jobs.[].id
            // - _embedded.jobs.[].status
            $.getJSON(endpoint, function(data) {
                for (var entry in data._embedded.jobs) {
                    var type = _this.determineJobType(data._embedded.jobs[entry].id);

                    if (type == 'build') {
                        jobUpdater.updateBuildJob(data._embedded.jobs[entry]);
                    } else if (type == 'push') {
                        jobUpdater.updatePushJob(data._embedded.jobs[entry]);
                    }
                }
            });
        }
    },
    storeInitialJobs: function() {
        var _this = this;

        $(this.jobTarget).each(function(index, item) {
            var $item = $(item);
            var status = $item.text().trim();

            var uniqueId = $item.data('push');
            if (typeof uniqueId === 'undefined') {
                uniqueId = $item.data('build');
            }

            _this.jobs[uniqueId] = status;
        });
    },
    determineJobType: function(jobId) {
        var type = jobId.charAt(0).toUpperCase();

        if (type === 'B') {
            return 'build';
        } else if (type === 'P') {
            return 'push';
        }
    },
    getUTCTime: function() {
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
};
