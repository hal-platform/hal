var $ = require('jquery');

exports.module = {
    interval: 5,
    mode: 'table', // "table" for global build table, "build" for individual build status page
    pendingClass: 'status-icon--other',
    thinkingClass: 'status-icon--thinking',
    successClass: 'status-icon--success',
    failureClass: 'status-icon--error',
    buildTarget: '[data-build]',

    init: function() {
        var _this = this;

        var $builds = $(this.buildTarget);
        $builds.each(function(index, item) {
            var $item = $(item);
            var status = $item.text().trim();

            if (status == 'Waiting' || status == 'Building') {
                $item
                    .removeClass(_this.pendingClass)
                    .addClass(_this.thinkingClass);

                _this.startUpdateTimer($item);
            }
        });

        return $builds;
    },
    generateUrl: function(buildId, type) {
        if (type === 'api-update') {
            return '/api/builds/' + buildId;

        } else if (type === 'push') {
            return '/builds/' + buildId + '/push';
        }
    },
    checkStatus: function($elem) {
        var _this = this;
        var id = $elem.data('build');
        var endpoint = this.generateUrl(id, 'api-update');
        console.log(endpoint);

        // Requires these properties:
        // - id
        // - status
        // - start.text
        // - end.text
        $.getJSON(endpoint, function(data) {
            var currentStatus = data.status;
            $elem.text(currentStatus);

            // console.log('Build ' + id + ' status: ' + currentStatus);

            if (currentStatus == 'Waiting' || currentStatus == 'Building') {
                // If still pending, fire up a countdown for the next callback in the chain.
                _this.startUpdateTimer($elem);

            } else if (currentStatus == 'Success') {
                $elem
                    .removeClass(_this.thinkingClass)
                    .addClass(_this.successClass);

            } else {
                $elem
                    .removeClass(_this.thinkingClass)
                    .addClass(_this.failureClass);
            }

            if (_this.mode == 'table') {
                _this.updateTable(data, $elem);
            } else {
                _this.updateBuild(data, $elem);
            }
        });
    },
    startUpdateTimer: function($elem) {
        var _this = this;

        var timer = window.setTimeout(function() {
            _this.checkStatus($elem);
        }, _this.interval * 1000);
    },
    updateBuild: function(data, $elem) {
        var $container = $elem.closest('dl');
        var $hdr;

        if (data.status == 'Success') {
            // Add push link if present
            $('.js-build-push')
                .html('<a class="btn btn--action" href="' + this.generateUrl(data.id, 'push') + '">Push Build</a>');

            // Replace success messaging
            $hdr = $('[data-success]');
            if ($hdr.length == 1) {
                $hdr.text($hdr.data('success'));
            }

        } else if (data.status == 'Error') {
            // Replace success messaging
            $hdr = $('[data-failure]');
            if ($hdr.length == 1) {
                $hdr.text($hdr.data('failure'));
            }
        }

        if (data.start !== null) {
            var $start = $container.children('.js-build-start');
            if ($start.length > 0 && $start.children('time').length === 0) {
                $start.html('<time datetime="' + data.start.datetime + '"></time>');
            }
        }

        if (data.end !== null) {
            var $end = $container.children('.js-build-end');
            if ($end.length > 0 && $end.children('time').length === 0) {
                $end.html('<time datetime="' + data.end.datetime + '"></time>');
            }
        }
    },
    updateTable: function(data, $elem) {
        var $container = $elem.closest('tr');

        if (data.status == 'Success') {
            // Add push link if present
            $container
                .children('.js-build-push')
                .html('<a class="btn btn--tiny" href="' + this.generateUrl(data.id, 'push') + '">Push</a>');
        }
    }
};
