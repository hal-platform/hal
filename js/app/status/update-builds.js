var $ = require('jquery');
var formatter = require('../util/time-formatter');

module.exports = {
    interval: 5,
    mode: 'table', // "table" for global build table, "build" for individual build status page
    pendingClass: 'status-icon--warning',
    thinkingClass: 'status-icon--thinking',
    successClass: 'status-icon--success',
    failureClass: 'status-icon--error',
    buildTarget: '[data-build]',

    init: function() {
        var _this = this;

        var $builds = $(this.buildTarget);
        $builds.each(function(index, item) {
            var $item = $(item);
            var status = $item.data('status').trim();

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
        }
    },
    checkStatus: function($elem) {
        var _this = this;
        var id = $elem.data('build');
        var endpoint = this.generateUrl(id, 'api-update');
        // console.log(endpoint);

        // Requires these properties:
        // - id
        // - status
        // - start
        // - end
        // - ? _links.start_push_page.href
        $.getJSON(endpoint, function(data) {
            var currentStatus = data.status;
            $elem.data('status', currentStatus); // protip: dom is not updated

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

        $elem.text($elem.data('status'));

        if (data.status == 'Success') {
            // Add push link if present
            if (data._links.hasOwnProperty('start_push_page')) {
                $('.js-build-push')
                    .html('<a class="btn btn--action" href="' + data._links.start_push_page.href + '">Push Build</a>');
            }

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

        if (data.start) {
            var $start = $container.children('.js-build-start');
            if ($start.length > 0 && $start.children('time').length === 0) {
                $start.html(this.createTimeElement(data.start));
            }
        }

        if (data.end) {
            var $duration = $container.children('.js-build-duration');
            if ($duration.length > 0 && $duration.children('time').length === 0) {
                $duration.html(this.createTimeDuration(data.start, data.end));
            }
        }
    },
    updateTable: function(data, $elem) {
        var $container = $elem.closest('tr');

        if (data.status == 'Success') {
            // Add push link if present
            if (data._links.hasOwnProperty('start_push_page')) {
                $container
                    .children('.js-build-push')
                    .html('<a class="btn btn--tiny" href="' + data._links.start_push_page.href + '">Push</a>');
            }
        }
    },
    createTimeElement: function(time) {
        var formatted = formatter.formatTime(time);
        return '<time datetime="' + time + '" title="' + formatted.absolute + '">' + formatted.relative + '</time>';
    },
    createTimeDuration: function(start, end) {
        var duration = formatter.calculateDuration(start, end),
            formatted = formatter.formatDuration(duration);
        return '<time datetime="' + duration + '" title="' + formatted.absolute + '">' + formatted.relative + '</time>';
    },
    // formatDuration: function(start, end) {
    //     var start = moment(start),
    //         end = moment(end),
    //         stupidduration = end.diff(start, 'seconds');

    //     var minutes = stupidduration / 60,
    //         seconds = stupidduration % 60;

    //     var iso = "PT" + minutes + "M" + seconds + "S",
    //         duration = moment.duration(iso),
    //         relative = duration.humanize(),
    //         absolute = duration.minutes() + " minutes, " + duration.seconds() + " seconds";

    //     return {
    //         iso: iso,
    //         absolute: absolute,
    //         relative: relative
    //     };
    // }
};
