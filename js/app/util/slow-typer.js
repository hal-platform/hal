import 'jquery';
import Typed from 'typed.js';

let textTarget = 'text';

function initSlowTyper(target) {
    $(target).each((i, el) => {
        let initial = $(el).text(),
            text = $(el).data(textTarget);

        var typed = new Typed(el, {
            strings: [initial, text],
            showCursor: true,
            startDelay: 1000,
            typeSpeed: 30,
        });

        typed.start();

    });
}

export { initSlowTyper };
