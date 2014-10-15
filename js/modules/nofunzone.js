define(['jquery'], function($) {
    return {
        target: '[data-nofunzone]',
        givenAttr: 'nofunzone',
        freudAttr: 'freud',
        $target: null,

        delay: 2500,
        action: 0,

        init: function() {

            this.$target = $(this.target);
            var given = this.$target.data(this.givenAttr),
                freud = this.$target.data(this.freudAttr);

            if (given && freud && given.length && freud.length) {
                // Add cursor
                this.$target.addClass('univCursor');

                // Type
                this.type(given, freud);

                // Remove cursor
                var trg = this.$target;
                setTimeout(function() {
                    trg.removeClass('univCursor');
                }, this.delayer(false));
            }
        },
        type: function(given, freud) {
            var now = this.$target.text(),
                initialLength = now.length,
                cb = null;

            // If the target text has a prefix of "Hello, ", adjust the length so we do not remove it.
            var match = /^[\s]*Hello, ([\x21-\x7E\s]+)$/i.exec(now);
            if (match !== null && match.length > 0) {
                initialLength = match[1].length;
            }

            // Remove initial
            for (i = 0; i < initialLength; i++) {
                this.action++;
                cb = this.deleteChar(this.$target);
                setTimeout(cb, this.delayer(false));
            }

            // Add freudian slip
            for (i = 0; i < freud.length; i++) {
                this.action++;
                cb = this.addChar(this.$target, freud.charAt(i));
                setTimeout(cb, this.delayer(true));
            }

            // let that sink for a second
            this.delay += 2000;

            // Remove freud
            for (i = 0; i < freud.length; i++) {
                this.action++;
                cb = this.deleteChar(this.$target);
                setTimeout(cb, this.delayer(false));
            }

            // Add given
            for (i = 0; i < given.length; i++) {
                this.action++;
                cb = this.addChar(this.$target, given.charAt(i));
                setTimeout(cb, this.delayer(true));
            }
        },
        deleteChar: function($target) {
            return function () {
                $target.text($target.text().slice(0, -1));
            };
        },
        addChar: function($target, next) {
            return function() {
                var current = $target.text();
                $target.text(current + next);
            };
        },
        delayer: function (addSlowDown) {
            // if 4th delete/add, add extra delay time
            if (addSlowDown && this.action % 4 === 0) {
                this.delay += 400;
            }
            this.delay += 100;

            return this.delay;
        }
    };
});
