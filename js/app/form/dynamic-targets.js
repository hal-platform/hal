import 'jquery';

let target = '.js-dynamic-targets';

var $container = null;

var init = function() {
    $container = $(target);
    if ($container.length !== 0) {
        attach();
    }
};

function attach() {
    $container
        .find('#server')
        .on('change', function() {

            var $selected = $('option:selected', this),
                selectedType = $selected.data('target-type');

            toggle(selectedType);
        })
        .trigger('change');
}

function toggle(target_type) {
    $container.find('li[data-type-specific]').hide();

    if (target_type === 'rsync') {
        $container.find('li[data-rsync]').show();

    } else if (target_type === 'eb') {
        $container.find('li[data-eb]').show();

    } else if (target_type === 'cd') {
        $container.find('li[data-cd]').show();

    } else if (target_type === 's3') {
        $container.find('li[data-s3]').show();

    } else if (target_type === 'elb') {
        $container.find('li[data-elb]').show();

    } else if (target_type === 'script') {
        $container.find('li[data-script]').show();
    }
}

export default init;
