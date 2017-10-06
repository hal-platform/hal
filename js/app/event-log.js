import 'jquery';
import AnsiUp from 'ansi_up'
import generateIcon from './util/icon';
import xssFilters from 'xss-filters'

let target = '[data-log][data-log-loadable=1]',
    loaderAnchor = '.js-event-logs-loader',
    loadingStates = {},
    expandHTML = generateIcon('chevron-down') + ' Expand',
    closeHTML = generateIcon('cross-2') + ' Close';

var init = () => {
    var loadableLogs = $(target);

    if (loadableLogs.length > 0) {
        loadableLogs.each((index, container) => attachLoader(container));
    }
};

function attachLoader(logTarget) {
    let $log = $(logTarget),
        $container = $log.find(loaderAnchor),

        $loader = $(`<a href="#">${expandHTML}</a>`);

    $container.append($loader);
    $loader.on('click.eventlog', loadLog);
}

function loadLog(e) {
    e.preventDefault();

    let $anchor = $(e.target),
        logID = $anchor
            .closest('tbody[data-log]')
            .data('log'),
        loaderURL = `/api/job-events/${logID}`;

    // Sanity check
    if (logID === undefined) {
        return;
    }

    var $context = $(`tr#log-context-${logID}`);

    if ($context.length > 0) {
        // already loaded?
        var btn = $context.is(":visible") ? expandHTML : closeHTML;
        $anchor.html(btn);

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
                prop = xssFilters.inHTMLData(prop);
                prop = AnsiUp.ansi_to_html(prop);
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

    $parent
        .find(`${loaderAnchor} a`)
        .html(closeHTML);

    loadingStates[logID] = false;
}

export default init;
