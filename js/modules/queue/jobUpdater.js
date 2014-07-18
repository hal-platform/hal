define(['jquery', 'handlebars'], function($, handlebars) {
    return {
        buildTemplate: null,
        pushTemplate: null,

        pendingClass: 'status-before--other',
        thinkingClass: 'status-before--thinking',
        successClass: 'status-before--success',
        failureClass: 'status-before--error',

        init: function() {
            // compile templates
            var buildSource = $("#build-template").html();
            this.buildTemplate = handlebars.compile(buildSource);

            var pushSource = $("#push-template").html();
            this.pushTemplate = handlebars.compile(pushSource);
        },

        addBuildJob: function(build) {
            var buildId = String(build.id);
            var context = {
                buildId: buildId,
                uniqueId: build.uniqueId,
                buildIdShort: buildId.slice(0, 10),
                buildStatusStyle: this.determineStatusStyle(build.status),
                buildStatus: build.status,
                environmentName: build._links.environment.title,
                repoId:  build._embedded.repository.id,
                repoName: build._embedded.repository.key,
                buildTime: build.created.text
            };

            return this.buildTemplate(context);
        },
        addPushJob: function(push) {
            var pushId = String(push.id);
            var context = {
                pushId: pushId,
                uniqueId: push.uniqueId,
                pushIdShort: pushId.slice(0, 10),
                pushStatusStyle: this.determineStatusStyle(push.status),
                pushStatus: push.status,
                environmentName: push._embedded.build._links.environment.title,
                serverName: push._embedded.deployment._links.server.title,
                repoId:  push._embedded.build._embedded.repository.id,
                repoName: push._embedded.build._embedded.repository.key,
                pushTime: push.created.text
            };

            return this.pushTemplate(context);
        },

        updatePushJob: function(job) {
            var $elem = $('[data-push="' + job.id + '"]');
            var currentStatus = job.status;
            $elem.text(currentStatus);

            if (currentStatus == 'Waiting' || currentStatus == 'Pushing') {
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
            $container
                .children('.js-start-date')
                .text(job.start.text);
        },
        updateBuildJob: function(job) {
            var $elem = $('[data-build="' + job.id + '"]');
            var currentStatus = job.status;
            $elem.text(currentStatus);

            if (currentStatus == 'Waiting' || currentStatus == 'Building') {
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
            $container
                .children('.js-start-date')
                .text(job.start.text);
        },

        stopThinking: function(jobTarget) {
            var _this = this;

            $(jobTarget).each(function(index, item) {
                var $elem = $(item);
                if ($elem.hasClass(_this.thinkingClass)) {
                    $elem
                        .removeClass(_this.thinkingClass)
                        .addClass(_this.pendingClass);
                }
            });
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
