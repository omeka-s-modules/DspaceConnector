(function($) {
    
    $(document).ready(function() {
        $('a.get-collections').on('click', function(e) {
            e.preventDefault();
            var dspaceUrl = $('#api-url').val();
            if (dspaceUrl == '') {
                alert('Try again with the dspace url');
                return;
            }
            var url = 'http://localhost/Omeka3/admin/dspace-connector/index/fetch';
            $.ajax({
                'url'  : url,
                'data' : {'link' : 'collections', 'dspaceUrl' : dspaceUrl },
                'type' : 'get',
                'dataType' : 'json'
            }).done(function(data) {
                data.forEach(writeCollectionLi);
                //console.log(data);
            }).error(function(data) {
                alert('Something went wrong.');
            });
        });
    });
    
    function writeCollectionLi(collectionObj) {
        var template = $('li.collection.template').clone();
        template.removeClass('template');
        template.find('.label').html(collectionObj.name);
        
        template.find('p.description').html(collectionObj.introductoryText);
        $('ul.collections input').val(collectionObj.link);
        $('ul.collections').append(template);
    }
})(jQuery);

