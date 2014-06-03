define(['jquery'], function($) {
    return {
        target: '.terminal pre',
        scale: 40,
        init: function() {
            var container = $(this.target);

            // pop the color immediately. This is done asap so the user doesn't get any weird
            // visibility flashes while the content is wrapped in spans
            var color = container.css('color');
            container.css('color', 'transparent');

            container.append('<span class="cursor typed"></span>');

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

                $(this).replaceWith($.map(this.nodeValue.split(''), function(character) {
                   return '<span>' + character + '</span>';
                }).join(''));
            });
        },
        typeCharacters: function($container) {
            var $characters = $container.children('span');
            var delay = 0;
            var fullText = '';
            var longDelay = 7;
            var scale = this.scale;
            var typoDelay = 10;

            $characters.each(function(index, el) {
                var $char = $(el);
                var char = $char.text();
                var isALetter = /[A-Za-z]/.test(char);
                var makeATypo = _getRandomNumberBetween(1, fullText.length) === 30 && isALetter;

                fullText += char;
                delay += !isALetter && char !== ' ' ? longDelay : _getRandomNumberBetween(1, 3);

                setTimeout(function() {
                    var $oopsChar;
                    var oopsChar;

                    if (makeATypo) {
                        $oopsChar = $($characters.get(index + _getRandomNumberBetween(1, 2)));
                        oopsChar = $oopsChar.text();

                        if (char === char.toUpperCase()) {
                          oopsChar = oopsChar.toUpperCase();
                        } else {
                          oopsChar = oopsChar.toLowerCase();
                        }

                        $char.text(oopsChar);
                        $char.addClass('typed');

                        setTimeout(function() {
                            $char.removeClass('typed');

                            setTimeout(function() {
                                $char.text(char);
                                $char.addClass('typed');
                            }, typoDelay * scale / 2);
                        }, typoDelay * scale / 2);
                    } else {
                        $char.addClass('typed');
                    }
                }, delay * scale);

                if (makeATypo) {
                    delay += typoDelay;
                }
            });

            function _getRandomNumberBetween(min, max) {
                return Math.ceil(Math.random(min, max) * max);
            }
        }
    };
});
