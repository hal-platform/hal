import components from './components';
import SlowTyper from './app/util/slow-typer';
import TerminalTyper from './app/util/terminal-typer';
import reltime from './app/util/relative-time';
import svg4everybody from 'svg4everybody';

let routingSelector = 'data-js-components';

function start() {

    var requested = document.querySelector('head').getAttribute(routingSelector);
    if (requested === "undefined" || requested === null) {
        return;
    }

    // Load components
    requested.split(' ').map(function(component) {
        if (components.hasOwnProperty(component)) {
            component = components[component];
            component();
        } else {
            console.log("Component not found: " + component);
        }

    });
}

// Start up
start();

// global utilities
TerminalTyper('.terminal__entry', '.terminal__text');
SlowTyper('.js-slow-typed');
reltime.init();

// Vendor utilities
svg4everybody();
