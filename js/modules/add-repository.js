define(['jquery'], function($) {
    return {
        users: {
            userTarget: '#github_user',
            dataStore: {},
            attach: function() {
                var _this = this;

                var target = $(this.userTarget);
                if (target.length !== 0) {
                    return _this.createToggle(target);
                }
            },
            createToggle: function(targetElem) {
                var _this = this;

                var toggle = $('<a class="js-form-toggle">');
                toggle.text('Load list from GitHub');

                targetElem.after(toggle);
                $(toggle).on('click', function(event) {
                    event.preventDefault();

                    var flash = $('<p>Loading...</p>');
                    targetElem.after(flash);

                    toggle.remove();
                    return $.get('/api/github/users', function(data) {
                        _this.dataStore = data;
                    })
                    .done(function() {
                        _this.replaceField(targetElem);
                        flash.remove();
                        targetElem.remove();
                    });
                });
            },
            replaceField: function(targetElem) {
                var select = $('<select>');
                select.attr('id', targetElem.attr('id'));
                select.attr('name', targetElem.attr('name'));

                targetElem.after(select);

                $('<option>').appendTo(select);
                this.attachOptions(select, targetElem.val());
            },
            attachOptions: function(select, currentSelection) {

                var orgGroup = $('<optgroup label="Organizations">');
                var userGroup = $('<optgroup label="Users">');

                for(var org in this.dataStore.organizations) {
                    $('<option>', {value: org, text: this.dataStore.organizations[org]}).appendTo(orgGroup);
                }
                for(var user in this.dataStore.users) {
                    $('<option>', {value: user, text: this.dataStore.users[user]}).appendTo(userGroup);
                }

                select.append(orgGroup);
                select.append(userGroup);
                select.val(currentSelection);
            }
        },
        repos: {
            userTarget: '#github_user',
            repoTarget: '#github_repo',
            target: null,
            originalTarget: null,
            dataStore: {},
            attach: function() {
                var _this = this;

                var target = $(this.repoTarget);
                if (target.length !== 0) {
                    return _this.createToggle(this.repoTarget);
                }
            },
            createToggle: function(target) {
                var _this = this;

                var toggle = $('<a class="js-form-toggle">');
                toggle.text('Load list from GitHub');

                $(target).after(toggle);
                $(toggle).on('click', function(event) {
                    event.preventDefault();
                    _this.target = $(target);

                    // we have to always grab this fresh because it may have changed
                    var selectedUser = $(_this.userTarget).val();

                    // error checking
                    if (selectedUser.length === 0) {
                        return _this.attachError('Please enter a valid GitHub User or Organization.');
                    }

                    _this.target.siblings('p').remove();

                    _this.getData(selectedUser)
                    .done(function() {
                        _this.replaceField();
                    })
                    .fail(function() {
                        _this.attachError('No repositories found.');
                    });
                });
            },
            attachError: function(text) {
                this.target.siblings('p').remove();

                var error = $('<p class="js-form-error">' + text + '</p>');

                this.target.after(error);

                // the original input is stored in originalTarget, so we have something to fall
                // back to in case github blows up or something. In those cases we want to allow
                // the user to enter a repository manually.
                if (this.originalTarget !== null) {
                    this.target.after(this.originalTarget);
                    this.target.remove();

                    this.target = this.originalTarget;
                    this.originalTarget = null;
                }
            },
            replaceField: function() {

                var selectedRepo = this.target.val();
                if (this.target.prop('tagName') === 'INPUT') {
                    var select = $('<select>');
                    select.attr('id', this.target.attr('id'));
                    select.attr('name', this.target.attr('name'));

                    this.target.after(select);
                    this.target.remove();

                    this.originalTarget = this.target;
                    this.target = select;
                }

                this.target.find('option').remove();
                $('<option>').appendTo(this.target);
                for(var repo in this.dataStore) {
                    $('<option>', {value: repo, text: this.dataStore[repo]}).appendTo(this.target);
                }

                this.target.val(selectedRepo);
            },
            getData: function(user) {
                var _this = this;
                this.dataStore = {};

                return $.get('/api/github/users/' + user + '/repositories', function(data) {
                    _this.dataStore = data;
                });
            }
        }
    };
});
