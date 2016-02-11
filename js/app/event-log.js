import 'jquery';
import ansiUp from 'ansi_up';
import generateIcon from './util/icon';

let target = '[data-log]',
    loaderAnchor = '.js-event-logs-loader',
    loadingStates = {};

var init = () => {
    var loadableLogs = $(target);

    if (loadableLogs.length > 0) {
        loadableLogs.each((index, container) => attachLoader(container));
    }
};

function attachLoader(logTarget) {
    let $log = $(logTarget),
        $container = $log.find(loaderAnchor),
        logID = $log.data('log-loader'),
        iconHTML = generateIcon('menu-pull-down'),
        $loader = $(`<a class="btn btn--tiny" data-log-id="${logID}">${iconHTML}</a>`);

    $container.append($loader);
    $loader.on('click.eventlog', loadLog);
}

function loadLog(e) {
    e.preventDefault();

    let $anchor = $(e.target),
        logID = $anchor
            .closest('tbody[data-log]')
            .data('log'),
        loaderURL = `/api/eventlogs/${logID}`;

    // Sanity check
    if (logID === undefined) {
        return;
    }

    var $context = $(`tr#log-context-${logID}`);

    if ($context.length > 0) {
        // already loaded?
        $context.toggle();
    } else {

        // To prevent someone smashing the button on first load
        if (loadingStates.hasOwnProperty(logID) && loadingStates[logID]) {
            return;
        }

        loadingStates[logID] = true;

        let $loading = $(`<tr id="log-loading-${logID}">
<td colspan="4" class="tl">Loading event data...</td>
</tr>`);

        $(`[data-log="${logID}"]`).append($loading);

        // load from ajax, show
        $.ajax(loaderURL, {context: { logID }})
            .fail(handleError)
            .done(handleSuccess);
    }
}

function handleSuccess(data) {
    renderRow(this.logID, data.data);
}

function handleError() {
    renderRow(this.logID, null);
}

function renderRow(logID, data) {
    let $parent = $(`[data-log="${logID}"]`),
        $loading = $parent.find(`#log-loading-${logID}`),
        $td = $('<td colspan="4" class="tl">'),
        $context = $(`<tr id="log-context-${logID}">`);

        $loading.remove();

    if (data === null) {
        $td.append('No data found');
    } else {
        for (let property in data) {
            let prop = data[property],
                $pre = $('<pre>'),
                $wrapper = $('<div class="pre-wrapper pre-ansi">');

            if (typeof prop !== 'string') {
                prop = JSON.stringify(prop, null, 4);
            } else {
                prop = ansiUp.ansi_to_html(prop);
            }

            $pre.appendTo($wrapper)
                .html(prop);

            $td.append(`<h4>${property}</h4>`)
                .append($wrapper)

        }
    }

    $context
        .append($td)
        .appendTo($parent);

    loadingStates[logID] = false;
}

export default init;
