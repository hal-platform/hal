import 'jquery';
import favico from 'favico.js';
import tpl from '../../nunjucks/eventlog.nunj';
import formatter from '../util/time-formatter';
import EventLogLoader from '../event-log';

module.exports = {
    interval: 5,
    mode: 'table', // "table" for global push table, "grid" for global push grid, "push" for individual push status page
    pendingClass: 'status-icon--warning',
    thinkingClass: 'status-icon--thinking',
    successClass: 'status-icon--success',
    failureClass: 'status-icon--error',
    pushTarget: '[data-push]',

    logTable: '.js-event-logs',
    logTarget: '[data-log]',

    $logTable: null,
    logs: {},

    favicon: null,

    init: function() {
        var _this = this;

        if (this.mode == 'release') {
            this.favicon = favico({
                animation: 'none',
                fontStyle: 'bolder'
            });

            // stub event logs, dont need to be updated further
            this.$logTable = $(this.logTable);
            if (this.$logTable.length > 0) {
                this.$logTable
                    .find(this.logTarget)
                    .each(function(index, item) {
                        var id = $(item).data('event');
                        _this.logs[id] = 'embedded';
                    });
            }
        }

        var $pushes = $(this.pushTarget);
        $pushes.each(function(index, item) {
            var $item = $(item);
            var status = $item.data('status').trim();

            if (status == 'pending' || status == 'deploying') {
                $item
                    .removeClass(_this.pendingClass)
                    .addClass(_this.thinkingClass);

                _this.startUpdateTimer($item);
            }
        });
    },
    generateUrl: function(pushId, type) {
        if (type === 'api-update') {
            return '/api/releases/' + pushId;
        } else if (type === 'events') {
            return '/api/releases/' + pushId + '/events?embed=events';
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

            // console.log('Release ' + id + ' status: ' + currentStatus);

            if (currentStatus == 'pending' || currentStatus == 'deploying') {
                // If still pending, fire up a countdown for the next callback in the chain.
                _this.startUpdateTimer($elem);

            } else if (currentStatus == 'success') {
                $elem
                    .removeClass(_this.thinkingClass)
                    .addClass(_this.successClass);

            } else {
                $elem
                    .removeClass(_this.thinkingClass)
                    .addClass(_this.failureClass);
            }

            if (_this.mode == 'release') {
                _this.updatePush(data, $elem);

            } else if (_this.mode == 'table') {
                _this.updateTable(data, $elem);

            } else if (_this.mode == 'grid') {
                _this.updateGrid(data, $elem);
            }

            _this.handleThinkingLogs(currentStatus);
            _this.handleLogExpanding(currentStatus);
        });

        this.checkLogs(id);
    },

    checkLogs: function(id) {
        if (this.$logTable === null) {
            return;
        }

        var _this = this;
        var logsEndpoint = this.generateUrl(id, 'events');

        // Requires these properties:
        // - count
        // - _embedded.events
        // - _embedded.events[].id
        // - _embedded.events[].name
        // - _embedded.events[].message
        // - _embedded.events[].created
        $.getJSON(logsEndpoint, function(data) {
            if (data.count < 1) {
                return;
            }

            if (!data.hasOwnProperty('_embedded')) {
                return;
            }

            if (!data._embedded.hasOwnProperty('events')) {
                return;
            }

            var logs = data._embedded.events,
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

        if (status == 'pending' || status == 'running' || status == 'deploying') {
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

    handleLogExpanding: function(status) {
        // Allow logs to be expandable when a job is done.

        if (this.$logTable === null) {
            return;
        }

        // is finished
        if (status == 'success' || status == 'failure') {
            // wait 2 seconds so any remaining logs can be loaded
            window.setTimeout(() => {
                this.$logTable
                    .find(this.logTarget)
                    .each((i, e) => { $(e).attr('data-log-loadable', '1'); });

                EventLogLoader();
            }, 2000);
        }
    },

    startUpdateTimer: function($elem) {
        var _this = this;

        window.setTimeout(function() {
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
            if (data.status == 'success') {
                // Unicode: U+2714 U+FE0E, UTF-8: E2 9C 94 EF B8 8E
                this.favicon.badge("✔︎", {
                    bgColor: '#0eb833',
                    type: 'rectangle'
                });
            } else if (data.status == 'failure') {
                // Unicode: U+2718, UTF-8: E2 9C 98
                this.favicon.badge('✘', {
                    bgColor: '#d63620',
                    type: 'rectangle'
                });

            } else if (data.status == 'pending' || data.status == 'deploying') {
                this.favicon.badge("?", {
                    bgColor: '#ff7c00',
                    type: 'circle'
                });
            }
        }
    },
    updateTable: function() {
        // derp
    },
    updateGrid: function(data, $elem) {
        $elem.text($elem.data('status'));
    },
    formatTime: function(time) {
        time = moment(time);

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

    buildEventRow: function(log) {

        var eventRegex = /^(build|release).([a-z]*)$/i,
            match = null,
            logName = '';

        match = eventRegex.exec(log.name);
        if (match !== null && match.length > 0) {
            logName = match.pop();
        }

        var context = {
            log: log,
            logEvent: logName,
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
