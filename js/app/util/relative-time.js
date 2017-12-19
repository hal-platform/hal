import 'jquery';
import { formatTime, formatDuration } from './time-formatter';

const POLL_INTERVAL = 10;
const SELECTOR_TARGET = 'time[datetime]';

function initRelativeTimes() {
    refreshTimes();

    // we cannot cache the times $elements to update because they can be added later by build/push updaters.
    window.setInterval(refreshTimes, POLL_INTERVAL * 1000);
}

function refreshTimes() {
    $(SELECTOR_TARGET).each(updateTime);
}

function updateTime() {
    let $this = $(this),
        time = $this.attr('datetime'),
        formatted;

    if (time.charAt(0) === 'P') {
        formatted = formatDuration(time);

    } else {
        formatted = formatTime(time);
    }

    $this.text(formatted.relative);
    $this.attr('title', formatted.absolute);
}

export { initRelativeTimes };
