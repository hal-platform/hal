import 'jquery';

module.exports = {
    target: '.js-app-permissions',

    $container: null,

    init: function() {
        this.$container = $(this.target);
        if (this.$container.length !== 0) {
            this.attach();
        }
    },
    attach: function() {
        var self = this;

        $('<a href="#">Add another user</a>')
            .insertAfter(self.$container)
            .on('click', function(e) {
                e.preventDefault();

                self.$container
                    .find('li:last-child')
                    .clone().appendTo(self.$container)
                    .find('input').val('');
            });
    },
    toggle: function(target_type) {
        this.$container.find('li[data-type-specific]').hide();
        if (target_type === 'rsync') {
            this.$container.find('li[data-rsync]').show();

        } else if (target_type === 'eb') {
            this.$container.find('li[data-eb]').show();

        } else if (target_type === 'cd') {
            this.$container.find('li[data-cd]').show();

        } else if (target_type === 's3') {
            this.$container.find('li[data-s3]').show();

        } else if (target_type === 'script') {
            this.$container.find('li[data-script]').show();
        }
    }
}
