import 'jquery';
import Typed from 'typed.js';

var init = (target, textTarget) => {
    $(target).each((i, el) => {
        let $text = $(textTarget),
            text = $text.text().trim();

        var typed = new Typed(el, {
            strings: [text],
            showCursor: false
        });

        typed.start();
    });
};

export default init;
