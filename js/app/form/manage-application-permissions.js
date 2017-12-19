import 'jquery';

let target = '.js-app-permissions';

var initApplicationPermissions = () => {
    let $container = $(target);
    if ($container.length !== 0) {
        attach($container);
    }
};

function attach($container) {
    $('<a href="#">Add another user</a>')
        .insertAfter($container)
        .on('click', function(e) {
            e.preventDefault();

            $container
                .find('li:last-child')
                .clone().appendTo($container)
                .find('input').val('');
        });
}

export { initApplicationPermissions };
