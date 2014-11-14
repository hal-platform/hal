define(['jquery'], function($) {
    return {
        target: '.terminal__entry',
        scale: 30,
        longDelay: 10,
        init: function() {
            var container = $(this.target);

            // pop the color immediately. This is done asap so the user doesn't get any weird
            // visibility flashes while the content is wrapped in spans
            var color = container.css('color');
            container.css('color', 'transparent');

            // Add empty space so vertical height of terminal is maintained when cursor hits the bottom
            container.append(" ");

            this.wrapCharacters(container);
            container.css('color', color);

            this.typeCharacters(container);
        },
        wrapCharacters: function($container) {
            $container.contents().each(function() {
                if (this.nodeType !== 3) {
                    return;
                }

                $(this).replaceWith($.map(this.nodeValue.trim().concat("\n").split(''), function(character) {
                   return '<span>' + character + '</span>';
                }).join(''));
            });
        },
        typeCharacters: function($container) {
            var longDelay = this.longDelay;
            var scale = this.scale;
            var $characters = $container.children('span');
            var $cursor = $('<span class="cursor typed"></span>');

            var delay = 0;
            var lastDelay = 0;

            $characters.each(function(index, el) {
                var $char = $(el);
                var char = $char.text();
                var isDelayedCharacter = /\r\n|\n|\r/.test(char);
                lastDelay = delay * scale;

                setTimeout(function() {
                    $char.addClass('typed').after($cursor);
                }, lastDelay);

                // Set the delay for the next character.
                delay += isDelayedCharacter ? longDelay : 1;
            });

            // remove the empty space inserted, this leaves only the cursor on the last line
            setTimeout(function() {
                $container.children('span:nth-last-child(2)').remove();
            }, lastDelay);
        }
    };
});
