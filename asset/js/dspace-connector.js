(function($) {
    $(document).ready(function() {
        $('#expand-all').on('click', function() {
            $('.communities .expand').click();
        });
        $('#collapse-all').on('click', function() {
            $('.communities .collapse').click();
        });
    });
})(jQuery)