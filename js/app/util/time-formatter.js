import Sugar from 'sugar-date';
import Duration from 'durationjs';

Sugar.extend({
    namespaces: [Date, Number]
});

module.exports = {
    threshold_6mo: (180).day(),
    threshold_2week: (14).day(),
    threshold_3day: (3).day(),
    threshold_8hr: (8).hour(),
    threshold_4hr: (4).hour(),
    threshold_1hr: (1).hour(),
    threshold_10min: (10).minute(),
    threshold_1min: (1).minute(),

    formatTime: function(iso8601) {
        var time = Date.create(iso8601),
            absolute = time.format('{Month} {d}, {yyyy} {h}:{mm} {TT}'),
            relative = time.relative(this.sugarRelativeAlgo(time));

        return {
            time: time,
            absolute: absolute,
            relative: relative
        };
    },
    formatDuration: function(iso8601) {

        var dur = new Duration(iso8601),
            data = dur.value(),
            min = dur.inMinutes(),
            relative;

            if (min > 60) {
                relative = 'A really long time';
            } else if (min > 1) {
                relative = data.minutes + " minutes";
                if (data.seconds > 0) {
                    relative += ", " + data.seconds + " seconds";
                }
            } else {
                relative = data.seconds + " seconds";
            }

        return {
            time: dur,
            absolute: dur.asClock(),
            relative: relative
        };
    },

    calculateDuration: function(start, end) {
        var startDate = Date.create(start),
            endDate = Date.create(end),

            seconds = (endDate.getTime() - startDate.getTime()) / 1000,
            hours = 0,
            minutes = Math.floor(seconds / 60);

            seconds = seconds % 60;

        if (minutes > 60) {
            hours = Math.floor(minutes / 60);
            minutes = minutes % 60;
        }


        return 'PT' + hours + 'H' + minutes + 'M' + seconds + 'S';
    },

    sugarRelativeAlgo: function(time) {
        var that = this;

        return function (value, unit, ms) {

            // if in future, just return default relative display
            if (ms < 0) {
                ms = Math.abs(ms);
            } else {
                return false;
            }

            var hours, minutes, seconds;

            // > 6 months
            if (ms > that.threshold_6mo) {
                return '{Mon} {d}, {yyyy}';

            // 2 weeks - 6 months
            } else if (ms > that.threshold_2week) {
                return '{Month} {d}';

            // 3 days - 2 weeks
            } else if (ms > that.threshold_3day) {
                return '{Mon} {d}, {h}:{mm} {TT}';

            // 8 hrs - 3 days
            } else if (ms > that.threshold_8hr) {
                if (time.isToday()) {
                    return '{h}:{mm} {TT}';
                } else {
                    return '{Dow}, {h}:{mm} {TT}';
                }
            // 4 hrs - 8 hrs
            } else if (ms > that.threshold_4hr) {
                return time.hoursAgo() + ' hr  ago';

            // 1 hr - 4 hr
            } else if (ms > that.threshold_1hr) {
                minutes = time.minutesAgo();
                hours = Math.floor(minutes / 60);
                minutes = minutes % 60;

                return hours + ' hr, ' + minutes + ' min ago';

            // 10 min - 1 hr
            } else if (ms > that.threshold_10min) {
                return time.minutesAgo() + ' min  ago';

            // 1 min - 10 min
            } else if (ms > that.threshold_1min) {
                seconds = time.secondsAgo();
                minutes = Math.floor(seconds / 60);
                seconds = seconds % 60;

                return minutes + ' min, ' + seconds + ' sec ago';

            // 0 - 1 min
            } else if (ms > 0) {
                return time.secondsAgo() + ' sec  ago';
            }
        };
    }
};
