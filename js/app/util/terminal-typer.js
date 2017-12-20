import 'jquery';
import Typed from 'typed.js';

function initTerminalTyper(target, textTarget) {
    $(target).each((i, el) => {
        let $text = $(textTarget),
            text = $text.text().trim();

        var typed = new Typed(el, {
            strings: [text],
            showCursor: false
        });

        typed.start();
    });
}

export { initTerminalTyper };
