import 'jquery';
import favico from 'favico.js';
import tpl from '../../nunjucks/eventlog.nunj';
import formatter from '../util/time-formatter';

module.exports = {
    interval: 5,
    mode: 'table', // "table" for global build table, "build" for individual build status page
    pendingClass: 'status-icon--warning',
    thinkingClass: 'status-icon--thinking',
    successClass: 'status-icon--success',
    failureClass: 'status-icon--error',
    buildTarget: '[data-build]',

    logTable: '.js-event-logs',
    logTarget: '[data-log]',

    $logTable: null,
    logs: {},

    favicon: null,

    init: function() {
        var _this = this;

        if (this.mode == 'build') {
            this.favicon = favico({
                animation: 'none',
                fontStyle: 'bolder'
            });

            // stub event logs, dont need to be updated further
            $logTable = $(this.logTable);
            if ($logTable.length > 0) {
                this.$logTable = $logTable;

                this.$logTable
                    .find(this.logTarget)
                    .each(function(index, item) {
                        var id = $(item).data('log');
                        _this.logs[id] = 'embedded';
                    });
            }
        }

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
    },
    generateUrl: function(buildId, type) {
        if (type === 'api-update') {
            return '/api/builds/' + buildId;
        } else if (type === 'logs') {
            return '/api/builds/' + buildId + '/logs?embed=logs';
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

            if (_this.mode == 'build') {
                _this.updateBuild(data, $elem);

            } else if (_this.mode == 'table') {
                _this.updateTable(data, $elem);
            }

            _this.handleThinkingLogs(currentStatus);
        });

        this.checkLogs(id);
    },

    checkLogs: function(id) {
        if (this.$logTable === null) {
            return;
        }

        var _this = this;
        var logsEndpoint = this.generateUrl(id, 'logs');

        // Requires these properties:
        // - count
        // - _embedded.logs
        // - _embedded.logs[].id
        // - _embedded.logs[].message
        // - _embedded.logs[].event
        // - _embedded.logs[].created
        $.getJSON(logsEndpoint, function(data) {
            if (data.count < 1) {
                return;
            }

            if (!data.hasOwnProperty('_embedded')) {
                return;
            }

            if (!data._embedded.hasOwnProperty('logs')) {
                return;
            }

            var logs = data._embedded.logs,
                hasNewLogs = false;

            for(var index in logs) {
                var log = logs[index];

                if (typeof _this.logs[log.id] == 'undefined') {
                    hasNewLogs = true;

                    _this.logs[log.id] = log.message;
                    _this.$logTable
                        .append(_this.buildEventRow(log));
                }
            }

            if (hasNewLogs) {
                _this.$logTable
                    .find('.js-empty-row')
                    .remove().end()
                    .find('.js-thinking-row')
                    .appendTo(_this.$logTable);
            }
        });
    },

    handleThinkingLogs: function(status) {
        if (this.$logTable === null) {
            return;
        }

        if (status == 'Waiting' || status == 'Building' || status == 'Pushing') {
            var $thinking = this.$logTable
                .find('.js-thinking-row');

            // If thinking row already exists, just move it to the bottom
            if ($thinking.length > 0) {
                $thinking.appendTo(this.$logTable);
            } else {
                $thinking = $('<tbody class="js-thinking-row">')
                    .append('<tr><td><span class="status-icon--thinking">Loading...</span></td></tr>')
                    .appendTo(this.$logTable);
            }

        } else {
            this.$logTable
                .find('.js-thinking-row').remove();
        }
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

            } else if (data.status == 'Waiting' || data.status == 'Building') {
                this.favicon.badge("?", {
                    bgColor: '#ff7c00',
                    type: 'circle'
                });
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

    buildEventRow: function(log) {

        var eventRegex = /^(build|push).([a-z]*)$/i,
            match = null,
            logEvent = '';

        match = eventRegex.exec(log.event);
        if (match !== null && match.length > 0) {
            logEvent = match.pop();
        }

        var context = {
            log: log,
            logEvent: logEvent,
            logCreated: this.createTimeElement(log.created)
        };

        return tpl.render(context);
    }
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
