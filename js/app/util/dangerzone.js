var $ = require('jquery');

exports.module = {
    target: '.js-dangerzone',

    $form: null,

    init: function() {
        this.$form = $(this.target);
        if (this.$form.length !== 0) {
            this.attach();
        }

    },

    attach: function() {
        var submitHandler = this.submitHandler.bind(this);

        this.$form
            .submit(submitHandler);
    },

    submitHandler: function(event) {
        event.preventDefault();

        var successHandler = this.successHandler.bind(this);

        var payload = {
                cmd: this.$form.find('input[name="cmd"]').val()
            },
            settings = {
                type: 'POST',
                dataType: 'json',
                data: payload
            };

        $.ajax(document.url, settings)
            .done(successHandler);
    },
    successHandler: function(response) {

        var cmd = response.cmd,
            output = response.output,
            exit = response.exit;

        this.$form.find('input[name="cmd"]').val('');

        var disp_term = this.clr('[', 'grey') +
            this.clr('HAL@HAL9000', 'yellow') +
            this.clr(']', 'grey') +
            this.clr(' > ', 'red') +
            this.clr(cmd, 'white') +
            '<br>';

        var disp_exit = this.clr('Exit: ', 'red') +
            this.clr(exit, 'white') +
            '<br>';

        var disp_output = this.clr('Output: ', 'red') +
            '<p>' + this.clr(output, 'white') + '</p>';

        $('.js-dz-output')
            .append(disp_term)
            .append(disp_exit)
            .append(disp_output);
    },
    clr: function(txt, clr) {
        return '<span style="color: ' + clr + '">' + txt + '</span>';
    }
};
