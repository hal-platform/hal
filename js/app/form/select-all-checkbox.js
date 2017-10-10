import 'jquery';

const MSG_SELECT = 'Select All';
const MSG_DESELECT = 'Deselect All';

let target = '.js-toggle-container',
    targetCheckboxes = '.js-toggle-box';

var $checks = null,
    toggled = false;

var init = function() {
    var $container = $(target);
    $checks = $(targetCheckboxes);

    if ($container.length && $checks.length > 1) {
        attach($container);
    }
};

function toggle($toggler) {
    toggled = !toggled;
    $checks.prop('checked', toggled);
    $toggler.text(toggled ? MSG_DESELECT : MSG_SELECT);
}

function attach($container) {
    let $toggler = $(`<a href="#">${MSG_SELECT}</a>`);

    $toggler.click(function(e) {
        e.preventDefault();
        toggle($toggler);
    });

    $container.html($toggler);
}

export default init;
