define(['jquery', 'modules/update-builds', 'modules/update-pushes'], function($, buildUpdater, pushUpdater) {
    return {
        interval: 20,

        // a list of the jobs we need to update
        jobs: {},
        lastRead: null,

        tableTarget: '#js-queue',
        init: function() {
            this.refresh();
            this.startRefreshTimer();
        },
        refresh: function() {
            var _this = this;
            var endpoint ='/api/queue';

            if (this.lastRead !== null) {
                endpoint += '?since=' + this.lastRead;
            }

            var now = new Date();
            this.lastRead = now.getUTCFullYear() + '-' +
                ('0' + (now.getUTCMonth()+1)).slice(-2) + '-' +
                ('0' + (now.getUTCDate())).slice(-2) +
                'T' +
                ('0' + (now.getUTCHours()+1)).slice(-2) + ':' +
                ('0' + (now.getUTCMinutes()+1)).slice(-2) + ':' +
                ('0' + (now.getUTCSeconds()+1)).slice(-2) +
                '-00:00';

            $.getJSON(endpoint, function(data) {
                if (data.length > 0) {
                    $('#js-emptyQueue').remove();
                    _this.addJobs(data.reverse());
                }
            });
        },
        initializeBuildUpdaters: function() {
            var $builds = buildUpdater.init();
            $builds.removeData('build');
        },
        initializePushUpdaters: function() {
            var $pushes = pushUpdater.init();
            $pushes.removeData('push');
        },
        startRefreshTimer: function() {
            var _this = this;

            return window.setInterval(function() {
                _this.refresh();
            }, _this.interval * 1000);
        },
        addJobs: function(data) {
            $queue = $(this.tableTarget);

            for(var entry in data) {
                var job = data[entry];
                var row, normalizedId;

                if (job.type == 'Build') {
                    normalizedId = 'build-' + job.id;
                    if (typeof this.jobs[normalizedId] == 'undefined') {
                        row = this.createBuildRow(job);

                        $queue.prepend(row);
                        this.jobs[normalizedId] = job.status;
                    }
                } else if (job.type == 'Push') {
                    normalizedId = 'push-' + job.id;

                    if (typeof this.jobs[normalizedId] == 'undefined') {
                        row = this.createPushRow(job);

                        $queue.prepend(row);
                        this.jobs[normalizedId] = job.status;
                    }
                }
            }
            console.log(this.jobs);

            // Start the updaters for individual jobs
            this.initializeBuildUpdaters();
            this.initializePushUpdaters();
        },
        createBuildRow: function(build) {
            var buildTime = '';
            var buildStatusStyle = this.determineStatusStyle(build.status);
            var buildId = String(build.id);

            if (build.startTime !== null) {
                buildTime = build.startTime;
            }

            var updateFlag = '';
            if (build.status == 'Waiting' || build.status == 'Building') {
                updateFlag = ' data-build="' + buildId + '"';
            }

            var html = '<tr id="build-' + buildId + '">' +
                '<td>Build (<a href="/build/' + buildId + '">' + buildId.slice(0, 10) + '</a>)</td>' +
                '<td><span class="status-before--' + buildStatusStyle + '"' + updateFlag + '>' +  build.status + '</span></td>' +
                '<td>' + build.environment.name + '</td>' +
                '<td><a href="/repositories/' + build.repository.id + '">' + build.repository.name + '</a></td>' +
                '<td>' + buildTime + '</td>' +
                '</tr>';

            return html;
        },
        createPushRow: function(push) {
            var pushTime = '';
            var pushStatusStyle = this.determineStatusStyle(push.status);
            var pushId = String(push.id);

            if (push.startTime !== null) {
                pushTime = push.startTime;
            }

            var updateFlag = '';
            if (push.status == 'Waiting' || push.status == 'Building') {
                updateFlag = ' data-push="' + pushId + '"';
            }

            var html = '<tr id="push-' + pushId + '">' +
                '<td>Push (<a href="/push/' + pushId + '">' + pushId.slice(0, 10) + '</a>)</td>' +
                '<td><span class="status-before--' + pushStatusStyle + '"' + updateFlag + '>' +  push.status + '</span></td>' +
                '<td>' + push.environment.name + ' : ' + push.server.name + '</td>' +
                '<td><a href="/repositories/' + push.repository.id + '">' + push.repository.name + '</a></td>' +
                '<td>' + pushTime + '</td>' +
                '</tr>';

            return html;
        },
        determineStatusStyle: function(status) {
            if (status == 'Success') {
                return 'success';
            } else if (status == 'Error') {
                return 'error';
            }

            return 'other';
        }
    };
});
