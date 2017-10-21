import 'jquery';

let target = '.js-dynamic-credentials';

var $container = null;

var init = function() {
    $container = $(target);
    if ($container.length !== 0) {
        attach();
    }
};

function attach() {
    $container
        .find('[data-credential-type]')
        .on('change', function() {

            var $selected = $(this).filter(':checked'),
                selectedType = $selected.data('credential-type');

            if (selectedType) {
                toggle(selectedType);
            }

        })
        .trigger('change');
}

function toggle(target_type) {
    $container.find('li[data-type-specific]').hide();

    if (target_type === 'aws_static') {
        $container.find('li[data-static]').show();

    } else if (target_type === 'aws_role') {
        $container.find('li[data-role]').show();

    } else if (target_type === 'privatekey') {
        $container.find('li[data-privatekey]').show();
    }
}

export default init;
