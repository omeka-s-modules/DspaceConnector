(function($) {
    $(document).ready(function() {
        $('#expand-all').on('click', function() {
            $('.communities .expand').click();
        });
        $('#collapse-all').on('click', function() {
            $('.communities .collapse').click();
        });
    });

    $(document).ready(function() {
        $('form').on('submit', function() {
	        $('.spinner-display').addClass('loading');
        });
    });
})(jQuery)
