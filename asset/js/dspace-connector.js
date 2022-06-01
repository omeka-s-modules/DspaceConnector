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
        // const spinnerDisplay = document.querySelector('.spinner-display');
	    // const btn = document.getElementById('index-submit');

        console.log(document.getElementById('index-submit'));

        $('.index-submit').on('click', function() {
            console.log('what');
	        spinnerDisplay.classList.add('loading');
        });
    });
})(jQuery)
