import 'jquery';

import { generateIcon } from '../util/icon';
import { formatTime } from '../util/time-formatter';
import { initEventLogLoader } from '../job/event-log-loader';

let logTableTarget = '.js-event-logs',
    logTarget = '[data-log]';

var $logTable = null,
    logs = {};

function initEventLogAdder() {
    // global state: logs, $logTable

    // stub event logs, dont need to be updated further
    $logTable = $(logTableTarget);
    if ($logTable.length <= 0) {
        return;
    }

    $logTable
        .find(logTarget)
        .each(function(index, item) {
            let id = $(item).data('log');
            logs[id] = 'embedded';
        });
}

// Requires these properties:
// - count
// - _embedded.events
// - _embedded.events[].id
// - _embedded.events[].name
// - _embedded.events[].message
// - _embedded.events[].created
function checkEventLogs($elem) {
    // global state: $logTable, logs
    if ($logTable === null) { return; }

    let id = $elem.data('build'),
        logsEndpoint = generateURL(id);

    $.getJSON(logsEndpoint, function(data) {
        if (data.count < 1) {
            return;
        }

        if (!data._embedded || !data._embedded.events) {
            return;
        }

        let logs = data._embedded.events,
            hasNewLogs = false;

        for(let index in logs) {
            let log = logs[index];

            if (typeof logs[log.id] == 'undefined') {
                hasNewLogs = true;

                logs[log.id] = log.message;
                $logTable
                    .append(renderEventRow(log));
            }
        }

        if (hasNewLogs) {
            $logTable
                .find('.js-empty-row')
                .remove().end()
                .find('.js-thinking-row')
                .appendTo($logTable);
        }
    });
}

function updateEventLogTable(jobStatus) {
    // global state: $logTable, logTarget
    if ($logTable === null) { return; }

    handleThinkingLogs($logTable, jobStatus);
    handleLogExpanding($logTable, logTarget, jobStatus);
}

// Handles the "Be patient, log messages are loading" message.
function handleThinkingLogs($table, jobStatus) {
    if (jobStatus == 'pending' || jobStatus == 'running' || jobStatus == 'deploying') {
        var $thinking = $table.find('.js-thinking-row');

        // If thinking row already exists, just move it to the bottom
        if ($thinking.length > 0) {
            $thinking.appendTo($table);
        } else {
            $thinking = $('<tbody class="js-thinking-row">')
                .append('<tr><td><span class="status-icon--thinking">Loading...</span></td></tr>')
                .appendTo($table);
        }

    } else {
        $table
            .find('.js-thinking-row')
            .remove();
    }
}

// Attach event log loader once the job is finished.
function handleLogExpanding($table, eventTarget, jobStatus) {
    // global state: $logTable, logTarget
    if ($table === null) { return; }

    // is finished
    if (jobStatus == 'success' || jobStatus == 'failure') {
        // wait 2 seconds so any remaining logs can be loaded
        window.setTimeout(() => {
            $table
                .find(eventTarget)
                .each((i, e) => { $(e).attr('data-log-loadable', '1'); });

            initEventLogLoader();
        }, 2000);
    }
}

function renderEventRow(event) {
    let event_name = formatEventName(event.name),
        event_time = formatTime(event.created),
        event_failure_class = event.status === 'failure' ? 'event-log--error' : '';

    let event_block_class = 'status-block--info';
    let event_icon = 'paragraph-justify-2';
    if (event.status === 'success') {
        event_block_class = 'status-block--success';
        event_icon = 'tick';
    }
    if (event.status === 'failure') {
        event_block_class = 'status-block--error';
        event_icon = 'spam-2';
    }

    let event_icon_svg = generateIcon(event_icon);

    let template = `
<tbody data-log="${event.id}">
    <tr class="${event_failure_class}">
        <td>
            <span class="${event_block_class}">
                ${event_icon_svg} ${event_name}
            </span>
        </td>
        <td>
            <time datetime="${event.created}" title="${event_time.absolute}">${event_time.relative}</time>
        </td>
        <td>${event.message}</td>
        <td class="tr js-event-logs-loader"></td>
    </tr>
</tbody>
`;

    return template;
}

function formatEventName(eventName) {
    let eventRegex = /^(build|release).([a-z]*)$/i,
        match = null,
        logName = '';

    match = eventRegex.exec(eventName);
    if (match !== null && match.length > 0) {
        logName = match.pop();
    }

    return logName.charAt(0).toUpperCase() + logName.slice(1);
}

function generateURL(buildID) {
    return '/api/builds/' + buildID + '/events?embed=events';
}

export { initEventLogAdder, checkEventLogs, updateEventLogTable };
