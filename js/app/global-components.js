import { initSlowTyper } from './util/slow-typer';
import { initTerminalTyper } from './util/terminal-typer';
import { initRelativeTimes } from './util/relative-time';

initTerminalTyper('.terminal__entry', '.terminal__text');
initSlowTyper('.js-slow-typed');
initRelativeTimes();
