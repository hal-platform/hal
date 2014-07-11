define(['jquery', 'handlebars', 'modules/update-builds', 'modules/update-pushes'], function($, handlebars, buildUpdater, pushUpdater) {
    return {
        pollingTimer: null,
        interval: 20,

        // a list of the jobs we need to update
        jobs: {},
        lastRead: null,

        queueTarget: '#js-queue tbody',
        $queue: null,
        buildTemplate: null,
        pushTemplate: null,
        init: function() {
            this.lastRead = this.getUTCTime();
            this.$queue = $(this.queueTarget);
            this.attachPollingToggle();

            // compile templates
            var buildSource = $("#build-template").html();
            this.buildTemplate = handlebars.compile(buildSource);

            var pushSource = $("#push-template").html();
            this.pushTemplate = handlebars.compile(pushSource);

            // Decrease the interval on build updates.
            buildUpdater.interval = 10;
            pushUpdater.interval = 10;

            // Initialize pending builds when the page loads.
            // buildUpdater.init();
            // pushUpdater.init();
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
            if (this.pollingTimer === null) {
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
            }

            // return the current id of the timer
            return this.pollingTimer;
        },
        refresh: function() {
            var _this = this;
            var endpoint ='/api/queue?since=' + this.lastRead;
            console.log(endpoint);
            this.lastRead = this.getUTCTime();

            $.getJSON(endpoint, function(data) {
                if (data.length > 0) {
                    $('#js-emptyQueue').remove();
                    _this.addJobs(data.reverse());
                }
            });
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

                        // start updater
                        if (job.status == 'Waiting' || job.status == 'Building') {
                            buildUpdater.buildTarget = '[data-build="' + job.id + '"]';
                            buildUpdater.init();
                        }
                    }
                } else if (job.type == 'Push') {
                    // only load jobs not already loaded
                    if (typeof this.jobs[job.uniqueId] == 'undefined') {
                        row = this.createPushRow(job);

                        this.$queue.prepend(row);
                        this.jobs[job.uniqueId] = job.status;

                        // start updater
                        if (job.status == 'Waiting' || job.status == 'Pushing') {
                            pushUpdater.buildTarget = '[data-push="' + job.id + '"]';
                            pushUpdater.init();
                        }
                    }
                }
            }
            console.log(this.jobs);
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
        }
    };
});
