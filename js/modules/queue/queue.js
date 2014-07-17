define(['jquery', 'handlebars', 'modules/queue/jobUpdater'], function($, handlebars, jobUpdater) {
    return {
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
            this.attachPollingButton();

            jobUpdater.init();

            // Need to load initial jobs list
            this.storeInitialJobs();
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

        attachPollingButton: function() {
            var _this = this;

            var $pollingButton = $('<a href="#" class="btn btn--destructive">Polling is disabled</a>');
            $pollingButton.click(function(e) {
                e.preventDefault();
                var currentTimer = _this.togglePolling($pollingButton);
            });

            this.$queue.closest('table').before($pollingButton);
        },
        togglePolling: function($pollingButton) {
            var _this = this;

            if (this.pollingTimer === null) {
                this.refresh();
                this.pollingTimer = this.startRefreshTimer();
                $pollingButton
                    .text('Polling is enabled')
                    .removeClass('btn--destructive');
            } else {
                clearInterval(this.pollingTimer);
                this.pollingTimer = null;
                $pollingButton
                    .text('Polling is disabled')
                    .addClass('btn--destructive');

                jobUpdater.stopThinking(this.jobTarget);
            }

            // return the current id of the timer
            return this.pollingTimer;
        },

        addJobs: function(data) {
            // required properties: uniqueId, type, status
            for(var entry in data) {
                var job = data[entry];
                var row;

                if (job.type == 'build') {
                    // only load jobs not already loaded
                    if (typeof this.jobs[job.uniqueId] == 'undefined') {
                        row = jobUpdater.addBuildJob(job);

                        this.$queue.prepend(row);
                        this.jobs[job.uniqueId] = job.status;
                    }
                } else if (job.type == 'push') {
                    // only load jobs not already loaded
                    if (typeof this.jobs[job.uniqueId] == 'undefined') {
                        row = jobUpdater.addPushJob(job);

                        this.$queue.prepend(row);
                        this.jobs[job.uniqueId] = job.status;
                    }
                }
            }
        },

        retrieveNewJobs: function() {
            var _this = this;

            var endpoint ='/api/queue?since=' + this.lastRead;
            this.lastRead = this.getUTCTime();

            // retrieve jobs created since last read
            $.getJSON(endpoint, function(data) {
                if (data.length > 0) {
                    $('#js-emptyQueue').remove();
                    _this.addJobs(data.reverse());
                }
            });
        },
        retrieveJobUpdates: function() {
            var _this = this;

            // a list of uniqueIds to update
            var jobsToUpdate = [];

            // build the list of jobs to update
            for (var uniqueId in this.jobs) {
                var currentStatus = this.jobs[uniqueId];
                if (currentStatus == 'Waiting' || currentStatus == 'Building' || currentStatus == 'Pushing') {
                    jobsToUpdate.push(uniqueId);
                }
            }

            // call api and update job rows
            var endpoint ='/api/queue-refresh/' + jobsToUpdate.join('+');
            $.getJSON(endpoint, function(data) {
                for (var entry in data) {
                    if (data[entry].type == 'build') {
                        jobUpdater.updateBuildJob(data[entry]);
                    } else {
                        jobUpdater.updatePushJob(data[entry]);
                    }
                }
            });
        },
        storeInitialJobs: function() {
            var _this = this;

            $(this.jobTarget).each(function(index, item) {
                var $item = $(item);
                var status = $item.text().trim();

                var uniqueId = $item.data('push');
                if (typeof uniqueId !== 'undefined') {
                    uniqueId = 'push-' + uniqueId;

                } else {
                    uniqueId = $item.data('build');
                    uniqueId = 'build-' + uniqueId;
                }

                _this.jobs[uniqueId] = status;
            });
        },

        getUTCTime: function() {
            var now = new Date();
            return now.getUTCFullYear() + '-' +
                ('0' + (now.getUTCMonth()+1)).slice(-2) + '-' +
                ('0' + (now.getUTCDate())).slice(-2) +
                'T' +
                ('0' + (now.getUTCHours()+1)).slice(-2) + ':' +
                ('0' + (now.getUTCMinutes()+1)).slice(-2) + ':' +
                ('0' + (now.getUTCSeconds()+1)).slice(-2) +
                '-0000';
        }
    };
});
