import 'jquery';

module.exports = {
    target: '.form--fav',

    $apps: null,

    init: function() {
        this.$apps = $(this.target);

        var attacher = this.attach.bind(this);

        if (this.$apps.length !== 0) {
            this.$apps.each(function (index, app) {
                attacher(app);
            });
        }

    },
    attach: function(form) {
        var $form = $(form),
            appID = $form.data('app-id');

        var successHandler = this.handleSuccess;

        var submitHandler = function(event) {
            event.preventDefault();

            var added = $form.children('.fav-added'),
                isAdded = added.length === 1;

            $.ajax({
                url: '/api/internal/settings/favorite-applications/' + appID,
                context: {
                    appID: appID,
                    isAdded: isAdded
                },
                type: isAdded ? 'DELETE' : 'PUT',
                dataType: 'json',
                contentType: 'application/json',
                data: JSON.stringify({})
            })
            .done(successHandler);
        };

        $form.on('submit', submitHandler);
    },
    handleSuccess: function() {
        // Regrab the form to update all rows that match
        var $form = $('[data-app-id=' + this.appID + ']');

        if ($form.length < 1) {
            return;
        }

        if (this.isAdded) {
            $form.children('label')
                .removeClass('fav-added')
                .addClass('fav-normal');

        } else {
            $form.children('label')
                .removeClass('fav-normal')
                .addClass('fav-added');
        }
    }
};
