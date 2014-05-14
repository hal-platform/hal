define(['jquery'], function($) {
    return {
        init: function() {
            var _this = this;
            $('#all-test').click(function() {
                _this.selectAll('test');
            });
            $('#all-beta').click(function() {
                _this.selectAll('beta');});
            $('#all-prod').click(function() {
                _this.selectAll('prod');
            });

            $('#all-reset').click(function() {
                $('input:checkbox').each(function(){
                    $(this).prop("checked", false);
                });
            });
        },
        selectAll: function(env) {
            $('input:checkbox[id^="sel-'+env+'"]').each(function() {
                $(this).prop("checked", true);
            });
        }
    };
});