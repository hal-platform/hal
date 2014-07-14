define(['jquery', 'handlebars'], function($, handlebars) {
    return {
        pollingTimer: null,
        interval: 10,

        // a list of the jobs we need to update
        jobs: {},
        lastRead: null,

        queueTarget: '#js-queue tbody',
        jobTarget: '[data-push], [data-build]',

        $queue: null,
        buildTemplate: null,
        pushTemplate: null,

        pendingClass: 'status-before--other',
        thinkingClass: 'status-before--thinking',
        successClass: 'status-before--success',
        failureClass: 'status-before--error',

        init: function() {
            this.lastRead = this.getUTCTime();
            this.$queue = $(this.queueTarget);
            this.attachPollingToggle();

            // compile templates
            var buildSource = $("#build-template").html();
            this.buildTemplate = handlebars.compile(buildSource);

            var pushSource = $("#push-template").html();
            this.pushTemplate = handlebars.compile(pushSource);

            // Need to load initial jobs list
            this.storeInitialJobs();
        },
        attachPollingToggle: function() {
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

                $(this.jobTarget).each(function(index, item) {
                    var $elem = $(item);
                    if ($elem.hasClass(_this.thinkingClass)) {
                        $elem
                            .removeClass(_this.thinkingClass)
                            .addClass(_this.pendingClass);
                    }
                });
            }

            // return the current id of the timer
            return this.pollingTimer;
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
        addJobs: function(data) {
            for(var entry in data) {
                var job = data[entry];
                var row;

                if (job.type == 'Build') {
                    // only load jobs not already loaded
                    if (typeof this.jobs[job.uniqueId] == 'undefined') {
                        row = this.createBuildRow(job);

                        this.$queue.prepend(row);
                        this.jobs[job.uniqueId] = job.status;
                    }
                } else if (job.type == 'Push') {
                    // only load jobs not already loaded
                    if (typeof this.jobs[job.uniqueId] == 'undefined') {
                        row = this.createPushRow(job);

                        this.$queue.prepend(row);
                        this.jobs[job.uniqueId] = job.status;
                    }
                }
            }
        },
        createBuildRow: function(build) {
            var buildId = String(build.id);

            var buildTime = '';
            if (build.startTime !== null) {
                buildTime = build.startTime;
            }

            var context = {
                buildId: buildId,
                uniqueId: build.uniqueId,
                buildIdShort: buildId.slice(0, 10),
                buildStatusStyle: this.determineStatusStyle(build.status),
                buildStatus: build.status,
                environmentName: build.environment.name,
                repoId:  build.repository.id,
                repoName: build.repository.name,
                buildTime: buildTime
            };

            return this.buildTemplate(context);
        },
        createPushRow: function(push) {
            var pushId = String(push.id);

            var pushTime = '';
            if (push.startTime !== null) {
                pushTime = push.startTime;
            }

            var context = {
                pushId: pushId,
                uniqueId: push.uniqueId,
                pushIdShort: pushId.slice(0, 10),
                pushStatusStyle: this.determineStatusStyle(push.status),
                pushStatus: push.status,
                environmentName: push.environment.name,
                serverName: push.server.name,
                repoId:  push.repository.id,
                repoName: push.repository.name,
                pushTime: pushTime
            };

            return this.pushTemplate(context);
        },
        determineStatusStyle: function(status) {
            if (status == 'Success') {
                return 'success';
            } else if (status == 'Error') {
                return 'error';
            }

            return 'other';
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
                '-00:00';
        },
        retrieveNewJobs: function() {
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

            var endpoint ='/api/queue-refresh/' + jobsToUpdate.join('+');

            // call api and update job rows
            $.getJSON(endpoint, function(data) {
                for (var entry in data) {
                    if (data[entry].type == 'Build') {
                        _this.updateBuildJob(data[entry]);
                    } else {
                        _this.updatePushJob(data[entry]);
                    }
                }
            });
        },
        updatePushJob: function(job) {
            var $elem = $('[data-push="' + job.id+ '"]');
            var currentStatus = job.status;
            $elem.text(currentStatus);

            if (currentStatus == 'Waiting' || currentStatus == 'Pushing') {
                // shrug
                $elem
                    .removeClass(this.pendingClass)
                    .addClass(this.thinkingClass);

            } else if (currentStatus == 'Success') {
                $elem
                    .removeClass(this.thinkingClass)
                    .addClass(this.successClass);

            } else {
                $elem
                    .removeClass(this.thinkingClass)
                    .addClass(this.failureClass);
            }

            var $container = $elem.closest('tr');

            // Add start time if present
            $container
                .children('.js-start-date')
                .text(job.startTime);
        },
        updateBuildJob: function(job) {
            var $elem = $('[data-build="' + job.id+ '"]');

            var currentStatus = job.status;
            $elem.text(currentStatus);

            if (currentStatus == 'Waiting' || currentStatus == 'Building') {
                // shrug
                $elem
                    .removeClass(this.pendingClass)
                    .addClass(this.thinkingClass);

            } else if (currentStatus == 'Success') {
                $elem
                    .removeClass(this.thinkingClass)
                    .addClass(this.successClass);

            } else {
                $elem
                    .removeClass(this.thinkingClass)
                    .addClass(this.failureClass);
            }

            var $container = $elem.closest('tr');

            // Add start time if present
            $container
                .children('.js-start-date')
                .text(job.startTime);
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
        }
    };
});
