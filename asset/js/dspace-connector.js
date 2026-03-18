(function($) {
    $(document).ready(function() {
        $('#expand-all').on('click', function() {
            $('.communities .expand').click();
        });
        $('#collapse-all').on('click', function() {
            $('.communities .collapse').click();
        });

        $(document).on('o:collapsed o:expanded', '.communities a[data-name]', function() {
            var toggle = $(this);
            var name = toggle.data('name');
            var action = toggle.attr('aria-label');
            toggle.attr('aria-label', action + ' ' + name);
        });
    });

    $(document).ready(function() {
        $('form').on('submit', function() {
	        $('.spinner-display').addClass('loading');
        });
    });
})(jQuery)
