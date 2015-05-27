var $ = require('jquery');
var nunjucks = require('nunjucks');

module.exports = {
    buildTemplate: null,
    pushTemplate: null,

    pendingClass: 'status-icon--warning',
    thinkingClass: 'status-icon--thinking',
    successClass: 'status-icon--success',
    failureClass: 'status-icon--error',

    init: function() {
        nunjucks.configure('views');
        // @todo compile templates
    },

    addBuildJob: function(build) {
        var buildId = String(build.id);
        var reference = String(build.reference.text);
        var initiator = 'Unknown';
        if (build._links.hasOwnProperty('user')) {
            if (build._links.user !== null) {
                initiator = build._links.user.title;
            }
        }

        var humanReference = this.determineGitref(reference);
        var context = {
            buildId: buildId,
            buildIdShort: this.formatBuildId(buildId),
            buildUrl: build.url,

            buildStatusStyle: this.determineStatusStyle(build.status),
            buildStatus: build.status,

            environmentName: build._links.environment.title,
            reference: humanReference,
            referenceType: this.determineGitrefType(humanReference),
            referenceUrl: build.commit.url,

            repoName: build._embedded.repository.title,
            repoStatusUrl: build._embedded.repository.url + '/status',

            initiator: initiator
        };

        return nunjucks.render('queue.build.html', context);
    },
    addPushJob: function(push) {
        var pushId = String(push.id);
        var buildId = String(push._embedded.build.id);
        var initiator = 'Unknown';
        if (push._links.hasOwnProperty('user')) {
            if (push._links.user !== null) {
                initiator = push._links.user.title;
            }
        }

        // kinda shitty way to determine server type but whatever
        var deployment = push._embedded.deployment,
            servername_or_whatever = deployment._links.server.title;
        if (servername_or_whatever.length === 0) {
            if (deployment['eb-environment'].length !== null) {
                servername_or_whatever = 'EB';
            } else if (deployment['ec2-pool'].length !== null) {
                servername_or_whatever = 'EC2';
            }
        }

        var context = {
            buildId: buildId,
            buildIdShort: this.formatBuildId(buildId),
            buildUrl: push._embedded.build.url,

            pushId: pushId,
            pushIdShort: this.formatPushId(pushId),
            pushUrl: push.url,

            pushStatusStyle: this.determineStatusStyle(push.status),
            pushStatus: push.status,

            environmentName: push._embedded.build._links.environment.title,
            serverName: servername_or_whatever,

            repoName: push._embedded.repository.title,
            repoStatusUrl: push._embedded.repository.url + '/status',

            initiator: initiator
        };

        return nunjucks.render('queue.push.html', context);
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
            return this.successClass;

        } else if (status == 'Error') {
            return this.errorClass;

        } else if (status == 'Waiting' || status == 'Building' || status == 'Pushing') {
            return this.thinkingClass;
        }

        return this.pendingClass;
    },
    determineGitref: function(gitref) {
        var formatted = this.formatGitref(gitref),
            size = 30;

        if (formatted.length <= size + 3) {
            return formatted;
        } else {
            return formatted.slice(0, size) + '...';
        }
    },
    determineGitrefType: function(gitref) {
        if (gitref.indexOf('Release') === 0) {
            return 'tag';
        }

        if (gitref.indexOf('Pull Request') === 0) {
            return 'pull';
        }

        if (gitref.indexOf('Commit') === 0) {
            return 'commit';
        }

        return 'branch';
    },
    formatGitref: function(gitref) {
        var prRegex = /^pull\/([\d]+)$/i,
            tagRegex = /^tag\/([\x21-\x7E]+)$/i,
            commitRegex = /^[a-f0-9]{40}$/i,
            match = null;

        match = prRegex.exec(gitref);
        if (match !== null && match.length > 0) {
            return 'Pull Request ' + match.pop();
        }

        match = tagRegex.exec(gitref);
        if (match !== null && match.length > 0) {
            return 'Release ' + match.pop();
        }

        match = commitRegex.exec(gitref);
        if (match !== null && match.length == 1) {
            return 'Commit ' + match.pop().slice(0, 7);
        }

        // Must be a branch
        return gitref;
    },
    formatBuildId: function (buildId) {
        var regex = /^b[a-zA-Z0-9]{1}.[a-zA-Z0-9]{7}$/i,
            match = null;

        match = regex.exec(buildId);
        if (match !== null && match.length == 1) {
            return match.pop().slice(6);
        }

        return buildId.slice(0, 10);
    },
    formatPushId: function (pushId) {
        var regex = /^p[a-zA-Z0-9]{1}.[a-zA-Z0-9]{7}$/i,
            match = null;

        match = regex.exec(pushId);
        if (match !== null && match.length == 1) {
            return match.pop().slice(6);
        }

        return pushId.slice(0, 10);
    },
};
