var $ = require('jquery');
var typed = require('typed');

exports.module = {
    target: '.terminal__entry',
    init: function() {

        var $target = $(this.target);
        var $text = $('.terminal__text');

        if ($target.length && $text.length) {
            var text = $text.text().trim();

            $target.typed({
                strings: [text],
                showCursor: false
            });
        }
    }
};
