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
            var reference = String(build.reference.text);

            var context = {
                uniqueId: build.uniqueId,

                buildId: buildId,
                buildIdShort: buildId.slice(0, 10),
                buildUrl: build.url,

                buildStatusStyle: this.determineStatusStyle(build.status),
                buildStatus: build.status,

                environmentName: build._links.environment.title,
                reference: reference.slice(0, 15),
                referenceUrl: build.commit.url,

                repoName: build._embedded.repository.key,
                repoUrl: build._embedded.repository.url,

                initiator: build._links.user.title
            };

            return this.buildTemplate(context);
        },
        addPushJob: function(push) {
            var pushId = String(push.id);
            var buildId = String(push._embedded.build.id);
            var context = {
                uniqueId: push.uniqueId,

                buildId: buildId,
                buildIdShort: buildId.slice(0, 10),
                buildUrl: push._embedded.build.url,

                pushId: pushId,
                pushIdShort: pushId.slice(0, 10),
                pushUrl: push.url,

                pushStatusStyle: this.determineStatusStyle(push.status),
                pushStatus: push.status,

                environmentName: push._embedded.build._links.environment.title,
                serverName: push._embedded.deployment._links.server.title,

                repoName: push._embedded.build._embedded.repository.key,
                repoUrl: push._embedded.build._embedded.repository.url,

                initiator: push._links.user.title
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
