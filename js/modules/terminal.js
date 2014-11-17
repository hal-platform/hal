define(['jquery', 'vendor/typed'], function($, typed) {
    return {
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
});
