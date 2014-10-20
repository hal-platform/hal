define(['jquery'], function($) {
    return {
        target: '[data-nofunzone]',
        givenAttr: 'nofunzone',
        freudAttr: 'freud',
        $target: null,

        fullFreud: null,
        fullGiven: null,
        delay: 2500,

        init: function() {

            this.$target = $(this.target);
            var given = this.$target.data(this.givenAttr),
                freud = this.$target.data(this.freudAttr);

            if (given && freud && given.length && freud.length) {
                // Add cursor
                this.$target.addClass('univCursor');

                // Type
                var steps = this.buildSteps(this.$target.text(), freud, given);
                this.runTyping(steps);

                // Add more delay to ensure these actions are at the very end of all steps
                this.delay += 500;

                // Remove cursor
                var trg = this.$target;
                setTimeout(function() {
                    trg.removeClass('univCursor');
                }, this.delayer());

                // If the tab is inactive, the order of the events may get messed up.
                // So just in case, at the very end, set to the correct name again.
                var fgiven = this.fullGiven;
                var setName = function() {
                    trg.text(fgiven);
                };
                setTimeout(setName, this.delayer());
            }
        },
        runTyping: function(steps) {
            var cb = null,
                useSlowdown = false;

            // create steps
            for (i = 0; i < steps.length; i++) {
                if (i > 0) {
                    useSlowdown = (steps[i].length > steps[i-1].length);
                }

                cb = this.setText(this.$target, steps[i]);
                setTimeout(cb, this.stepDelayer(i, useSlowdown));

                // let that sink for a second
                if (steps[i] == this.fullFreud) {
                    this.delay += 2000;
                }
            }

        },
        buildSteps: function(current, freud, given) {

            var initialLength = current.length,
                steps = [],
                fullFreud = null;

            // If the target text has a prefix of "Hello, ", adjust the length so we do not remove it.
            var match = /^[\s]*Hello, ([\x21-\x7E\s]+)$/i.exec(current);
            if (match !== null && match.length > 0) {
                initialLength = match[1].length;
            }

            // Remove initial
            for (i = 0; i < initialLength; i++) {
                current = current.slice(0, -1);
                steps.push(current);
            }

            // Add freudian slip
            for (i = 0; i < freud.length; i++) {
                current = current + freud.charAt(i);
                steps.push(current);
            }

            // record fully built freud name
            this.fullFreud = current;

            // Remove freud
            for (i = 0; i < freud.length; i++) {
                current = current.slice(0, -1);
                steps.push(current);
            }

            // Add given
            for (i = 0; i < given.length; i++) {
                current = current + given.charAt(i);
                steps.push(current);
            }

            // record fully built given name
            this.fullGiven = current;

            return steps;
        },
        setText: function($target, text) {
            return function () {
                $target.text(text);
            };
        },
        stepDelayer: function (step, addSlowDown) {
            // if 4th delete/add, add extra delay time
            if (addSlowDown && step % 4 === 0) {
                this.delay += 400;
            }
            this.delay += 100;

            return this.delay;
        },
        delayer: function () {
            this.delay += 100;

            return this.delay;
        }
    };
});
