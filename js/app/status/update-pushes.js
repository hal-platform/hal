var $ = require('jquery');
var formatter = require('../util/time-formatter');
var favico = require('favico');

module.exports = {
    interval: 5,
    mode: 'table', // "table" for global push table, "grid" for global push grid, "push" for individual push status page
    pendingClass: 'status-icon--warning',
    thinkingClass: 'status-icon--thinking',
    successClass: 'status-icon--success',
    failureClass: 'status-icon--error',
    pushTarget: '[data-push]',

    favicon: null,

    init: function() {
        var _this = this;

        if (this.mode == 'push') {
            this.favicon = favico({
                animation: 'none',
                fontStyle: 'bolder'
            });
        }

        var $pushes = $(this.pushTarget);
        $pushes.each(function(index, item) {
            var $item = $(item);
            var status = $item.data('status').trim();

            if (status == 'Waiting' || status == 'Pushing') {
                $item
                    .removeClass(_this.pendingClass)
                    .addClass(_this.thinkingClass);

                _this.startUpdateTimer($item);
            }
        });

        return $pushes;
    },
    generateUrl: function(pushId, type) {
        if (type === 'api-update') {
            return '/api/pushes/' + pushId;
        }
    },
    checkStatus: function($elem) {
        var _this = this;
        var id = $elem.data('push');
        var endpoint = this.generateUrl(id, 'api-update');
        // console.log(endpoint);

        // Requires these properties:
        // - id
        // - status
        // - start
        // - end
        $.getJSON(endpoint, function(data) {
            var currentStatus = data.status;
            $elem.data('status', currentStatus); // protip: dom is not updated

            // console.log('Push ' + id + ' status: ' + currentStatus);

            if (currentStatus == 'Waiting' || currentStatus == 'Pushing') {
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

            if (_this.mode == 'push') {
                _this.updatePush(data, $elem);
            } else if (_this.mode == 'table') {
                _this.updateTable(data, $elem);
            } else if (_this.mode == 'grid') {
                _this.updateGrid(data, $elem);
            }
        });
    },
    startUpdateTimer: function($elem) {
        var _this = this;

        var timer = window.setTimeout(function() {
            _this.checkStatus($elem);
        }, _this.interval * 1000);
    },
    updatePush: function(data, $elem) {
        var $container = $elem.closest('dl');

        $elem.text($elem.data('status'));

        if (data.start) {
            var $start = $container.children('.js-push-start');
            if ($start.length > 0 && $start.children('time').length === 0) {
                $start.html(this.createTimeElement(data.start));
            }
        }

        if (data.end) {
            var $duration = $container.children('.js-push-duration');
            if ($duration.length > 0 && $duration.children('time').length === 0) {
                $duration.html(this.createTimeDuration(data.start, data.end));
            }
        }

        // favicon
        if (this.favicon !== null) {
            if (data.status == 'Success') {
                // Unicode: U+2714 U+FE0E, UTF-8: E2 9C 94 EF B8 8E
                this.favicon.badge("✔︎", {
                    bgColor: '#0eb833',
                    type: 'rectangle'
                });
            } else if (data.status == 'Error') {
                // Unicode: U+2718, UTF-8: E2 9C 98
                this.favicon.badge('✘', {
                    bgColor: '#d63620',
                    type: 'rectangle'
                });

            } else if (data.status == 'Waiting' || data.status == 'Pushing') {
                this.favicon.badge("?", {
                    bgColor: '#ff7c00',
                    type: 'circle'
                });
            }
        }
    },
    updateTable: function(data, $elem) {
        // derp
    },
    updateGrid: function(data, $elem) {
        $elem.text($elem.data('status'));
    },
    formatTime: function(time) {
        var time = moment(time);

        return {
            absolute: time.format('MMM D, YYYY h:mm A'),
            relative: time.fromNow()
        };
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
