define(['jquery'], function($) {
    return {
        handleTarget: '#githubPullRequests',
        dataStore: {},
        init: function() {
            var target = $(this.handleTarget);
            if (target.length !== 0) {
                this.attach(target);
            }
        },
        attach: function(targetElem) {
            var _this = this;

            $(targetElem).on('click', function(event) {
                event.preventDefault();

                var flash = $('<p>Loading...</p>');
                targetElem.after(flash);

                var user = targetElem.data('user');
                var repo = targetElem.data('repo');

                targetElem.remove();
                return $.get('/api/github/users/' + user + '/repositories/' + repo + '/pullrequests', function(data) {
                    _this.dataStore = data;
                })
                .done(function() {
                    _this.addRadios(flash);
                    flash.remove();
                })
                .fail(function() {
                    flash.after('<p class="js-form-error">No pull requests found.</p>');
                    flash.remove();
                });
            });
        },
        addRadios: function(targetElem) {
            var list = $('<ul class="check-list">');
            var pr, input, label, description;

            for(var pull in this.dataStore) {
                pr = this.dataStore[pull];

                input = $('<input id="pr' + pr.number + '" type="radio" name="commitish" value="pull' + pr.number + '">');
                label = $('<label>')
                .prop('for', 'pr' + pr.number)
                .append(pr.title)
                .append(' (<a href="' + pr.url + '">PR #' + pr.number + '</a>)');

                description = $('<p>')
                .append('<span class="' + pr.state + '">[' + pr.state + ']</span>')
                .append(' <code>' + pr.to + ' : ' + pr.from + '</code>');

                $('<li class="pr-selection">')
                .append(input)
                .append(' ')
                .append(label)
                .append(description)
                .appendTo(list);
            }

            targetElem.after(list);
        }
    };
});
