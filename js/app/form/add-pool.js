var $ = require('jquery');

module.exports = {
    target: '.js-pool-form [data-pool]',
    emptyRow: '.js-no-deployment',
    removeTarget: '.js-remove-deployment',

    $pools: null,

    init: function() {
        this.$pools = $(this.target);

        var attacher;

        if (this.$pools.length !== 0) {
            attacher = this.attachAdd.bind(this);

            $(this.$pools).each(function (index, pool) {
                attacher(pool);
            });
        }

        var $removals = $(this.removeTarget);
        if ($removals.length !== 0) {
            attacher = this.attachRemove.bind(this);

            $removals.each(function (index, removal) {
                attacher(removal);
            });
        }
    },
    attachAdd: function(pool) {
        var $pool = $(pool),
            $btn = $pool.find('.btn');

        $btn.on('click', function(event) {
            event.preventDefault();
        });

        var url = $pool.find('form').attr('action');

        // Handler for "good" submit button
        // Submits form, performs error checking
        var _this = this,
            errorHandler = this.handleError,
            successHandler = this.handleSuccess;

        var submitHandler = function(event) {
            event.preventDefault();

            // submit to backend
            // pop errors
            // on success, append, clear errors, clear form
            var payload = {
                deployment: $pool.find('select[name="deployment"]').val()
            };

            // console.log(payload);
            $.ajax({
                context: {
                    context: _this,
                    pool: $pool
                },
                url: url,
                type: 'POST',
                dataType: 'json',
                contentType: 'application/json',
                data: JSON.stringify(payload)
            })
            .fail(errorHandler)
            .done(successHandler);
        };

        $btn.on('click', submitHandler);
    },
    handleError: function(data) {
        var errors = ['Internal Server Error.'];
        if (data.hasOwnProperty('responseJSON')) {
            errors = data.responseJSON.errors;
        }

        // reset
        this.context.resetMessages();

        // Add new errors
        $(this.pool)
            .find('form').append(this.context.buildError(errors));
    },
    handleSuccess: function(data) {
        // reset
        this.context.resetMessages();
        this.context.resetForms();

        var $pool = $(this.pool);

        // add success alert
        var $alert = $('<div>')
            .addClass('alert-bar--success');

        $('<h4>')
            .text('Deployment added.')
            .appendTo($alert);

        $pool
            .find('form').append($alert);

        var server = data.server,
            deployment = data.deployment,
            hostname = server.name;

        if (deployment.name.length > 0) {
            name = deployment.name;
        } else {
            if (server.type == 'elasticbeanstalk') {
                name = 'EB (' + hostname + ')';
            } else if (server.type == 'ec2') {
                name = 'EC2 (' + hostname + ')';
            } else if (server.type == 's3') {
                name = 'S3 (' + hostname + ')';
            } else {
                name = hostname;
            }
        }

        this.context.addDeployment($pool, deployment.id, name, data.remove_url);
    },
    addDeployment: function($pool, id, name, remove_url) {
        var $row = $('<li>')
            .append('<span class="split__title">' + name + '</span>')
            .append('<a class="js-remove-deployment" href="' + remove_url +'" data-deployment="' + id + '">Remove</a>');

        // add new row, remove empty row if there
        $pool.children('ul')
            .append($row)
            .find(this.emptyRow).remove();

        var removal = $pool.find('a[data-deployment="' + id + '"]');
        this.attachRemove(removal);
    },
    resetForms: function() {
        this.$pools
            .find('select[name="deployment"]').val('');
    },
    resetMessages: function() {
        this.$pools
            .find('.error-list').remove().end()
            .find('.alert-bar--success').remove();
    },

    attachRemove: function(removal) {
        var $removal = $(removal);

        $removal.on('click', function(event) {
            event.preventDefault();
        });

        var url = $removal.attr('href'),
            _this = this,
            errorHandler = this.handleRemovalError,
            successHandler = this.handleRemovalSuccess;

        var submitHandler = function(event) {
            event.preventDefault();

            $.ajax({
                context: {
                    context: _this,
                    removal: $removal
                },
                url: url,
                type: 'POST',
                dataType: 'json',
                contentType: 'application/json',
                data: JSON.stringify({})
            })
            .fail(errorHandler)
            .done(successHandler);
        };

        $removal.on('click', submitHandler);
    },
    handleRemovalError: function(data) {

        // reset
        this.context.resetMessages();

        // Add new errors
        this.removal
            .closest(this.context.target)
            .find('form').append(this.context.buildError(['An unknown error occured.']));
    },
    handleRemovalSuccess: function() {

        // reset
        this.context.resetMessages();

        var $removal = this.removal,
            $pool = $removal.closest(this.context.target);
            $alert = $('<div>')
                .addClass('alert-bar--success');

        $('<h4>')
            .text('Deployment removed.')
            .appendTo($alert);

        $removal
            .parent('li')
            .remove();

        $pool
            .find('form').append($alert);


        // if no deployments, add empty row
        if ($pool.find('a.js-remove-deployment').length === 0) {

        var $row = $('<li class="js-no-deployment">')
            .append('<span class="split__title">No deployments defined.</span>');

            $pool
                .children('ul')
                .append($row);
        }
    },

    buildError: function(errors) {
        var $ul = $('<ul>').addClass('error-list');
        errors.forEach(function(err) {
            $ul.append('<li>' + err + '</li>');
        });

        return $ul;
    }
};
