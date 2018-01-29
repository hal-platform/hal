import 'jquery';

let target = '.js-fav';

var $apps = null;

var initFavoriteApplications = () => {
    $apps = $(target);

    if ($apps.length > 0) {
        $apps.each(function (index, app) {
            attach(app);
        });
    }
};

function attach(form) {
    var $form = $(form),
        appID = $form.data('app-id'),
        url = $form.attr('action');

    var submitHandler = function(event) {
        event.preventDefault();

        var added = $form.children('.fav-added'),
            isAdded = added.length === 1;

// '/api/internal/settings/favorite-applications/' + appID

        $.ajax({
            url: url,
            context: {
                appID: appID,
                isAdded: isAdded
            },
            type: isAdded ? 'DELETE' : 'PUT',
            dataType: 'json',
            contentType: 'application/json',
            data: JSON.stringify({})
        })
        .done(handleSuccess);
    };

    $form.on('submit', submitHandler);
}

function handleSuccess() {
    // Regrab the form to update all rows that match
    var $form = $(`[data-app-id=${this.appID}]`);

    if ($form.length < 1) {
        return;
    }

    if (this.isAdded) {
        $form.children('label')
            .removeClass('fav-added')
            .addClass('fav-normal')
            .children('[aria-label]')
            .attr('aria-label', 'Add to favorites');

    } else {
        $form.children('label')
            .removeClass('fav-normal')
            .addClass('fav-added')
            .children('[aria-label]')
            .attr('aria-label', 'Remove from favorites');
    }
}

export { initFavoriteApplications };
