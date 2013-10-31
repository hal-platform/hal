$(function() {
    // toggle secondary nav
    $('.toggled-nav').hide();
    $('.toggle-nav').on('click', function() {
        $('.toggled-nav').slideToggle('fast');
        return false;
    });
});
