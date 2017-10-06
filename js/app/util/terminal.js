import 'jquery';
import Typed from 'typed.js';

module.exports = {
    target: '.terminal__entry',
    init: function() {

        var $target = $(this.target);
        var $text = $('.terminal__text');

        if ($target.length && $text.length) {
            var text = $text.text().trim();

            var typed = new Typed(this.target, {
                strings: [text],
                showCursor: false
            });

            typed.start();
        }
    }
};
