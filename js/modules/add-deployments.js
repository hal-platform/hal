define(['jquery'], function($) {
    return {
        target: '.js-add-deployment__form',
        badButton: '.js-add-deployment__button a',
        envListPrefix: '.js-add-deployment--',
        emptyRow: '.js-add-deployment__empty',

        $container: null,

        init: function() {
            this.$container = $(this.target);
            if (this.$container.length !== 0) {
                this.attach();
            }
        },
        attach: function() {
            var $container = this.$container,
                $form = $container.find('.form-fields'),
                $badBtn = $(this.badButton),
                $submitBtn = $container.find('input[type="submit"]'),
                $cancelBtn = $container.find('.secondary-action'),
                url = $container.find('form').attr('action');

            // Handler for original link to separate page
            // Modified to instead display the form inline on the current page
            var badHandler = function(event) {
                event.preventDefault();

                $container.show();
                $badBtn.hide();
            };

            // Handler for "good" submit button
            // Submits form, performs error checking
            var errorHandler = this.handleError.bind(this),
                successHandler = this.handleSuccess.bind(this);

            var submitHandler = function(event) {
                event.preventDefault();

                // submit to backend
                // pop errors
                // on success, append, clear errors, clear form
                var payload = {
                        server: $container.find('select[name="server"]').val(),
                        url: $container.find('input[name="url"]').val(),
                        path: $container.find('input[name="path"]').val(),
                    },
                    settings = {
                        type: 'POST',
                        dataType: 'json',

                        contentType: 'application/json',
                        data: JSON.stringify(payload)
                    };

                // console.log(payload);
                $.ajax(url, settings)
                    .fail(errorHandler)
                    .done(successHandler);
            };

            // Handler for "cancel" button
            // Hides inline form
            var resetMessages = this.resetMessages.bind(this),
                resetForm = this.resetForm.bind(this);

            var cancelHandler = function(event) {
                event.preventDefault();

                // reset
                resetMessages();
                resetForm();

                $container.toggle();
                $badBtn.toggle();
            };

            // Attach handlers
            $badBtn.on('click', badHandler);
            $submitBtn.on('click', submitHandler);
            $cancelBtn.on('click', cancelHandler);
        },
        handleError: function(data) {
            var errors = ['Internal Server Error.'];
            if (data.hasOwnProperty('responseJSON')) {
                errors = data.responseJSON.errors;
            }

            var $ul = $('<ul>').addClass('error-list');
            errors.forEach(function(err) {
                $ul.append('<li>' + err + '</li>');
            });

            // reset
            this.resetMessages();

            // Add new errors
            this.$container
                .find('form').before($ul);
        },
        handleSuccess: function(deployment) {
            // reset
            this.resetMessages();
            this.resetForm();

            // add success alert
            var $alert = $('<div>').addClass('alert-bar--success').text('Deployment Added.');
            this.$container
                .find('form').before($alert);

            var hostname = deployment._embedded.server.name,
                env =      deployment._embedded.server._embedded.environment.key;

            this.addDeployment(env, deployment.id, hostname, deployment.path, deployment.url);
        },
        addDeployment: function(env, id, hostname, path, url) {
            var $list = $(this.envListPrefix + env);
                $row = $('<tr>')
                .append('<td>' + hostname + '</td>')
                .append('<td>' + path + '</td>')
                .append('<td><a href="' + url + '"><code>' + url + '</code></a></td>')
                .append('<td></td>');

            // add new row, remove empty row if there
            $list
                .append($row)
                .find(this.emptyRow).remove();
        },
        resetForm: function() {
            this.$container
                .find('select[name="server"]').val('').end()
                .find('input[name="url"]').val('').end()
                .find('input[name="path"]').val('');
        },
        resetMessages: function() {
            this.$container
                .find('.error-list').remove().end()
                .find('.alert-bar--success').remove().end();
        }
    };
});
