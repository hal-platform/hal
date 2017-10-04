import 'jquery';
import Typed from 'typed.js';

module.exports = {
    target: '[data-nofunzone]',
    initialAttr: 'initial',
    givenAttr: 'nofunzone',
    freudAttr: 'freud',

    init: function() {
        var $target = $(this.target);
        var initial = $target.data(this.initialAttr),
            freud = $target.data(this.freudAttr),
            given = $target.data(this.givenAttr);

        if (given && given.length) {
            var typedStrings = [initial];

            if (freud && freud.length) {
                typedStrings.push(freud);
            } else {
                $target.text('');
            }

            typedStrings.push(given);

            var typed = new Typed(this.target, {
                strings: typedStrings,
                startDelay: 1000,
                typeSpeed: 50,
                backSpeed: 25,
                backDelay: 1000,
                onComplete: function() {
                    $target.parent().children('.typed-cursor').remove();
                }
            });

            typed.start();
        }
    }
};
