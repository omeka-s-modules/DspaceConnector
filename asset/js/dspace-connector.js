(function($) {
    $(document).ready(function() {
        $('a.get-collections').on('click', function(e) {
            $('ul.container').empty();
            e.preventDefault();
            var dspaceUrl = $('#api-url').val();
            if (dspaceUrl == '') {
                alert('Try again with the dspace url');
                return;
            }
            var url = 'dspace-connector/index/fetch';
            $.ajax({
                'url'  : url,
                'data' : {'link' : 'collections', 'dspaceUrl' : dspaceUrl },
                'type' : 'get',
                'dataType' : 'json'
            }).done(function(data) {
                data = JSON.parse(data.data);
                data.forEach(writeCollection, $('ul.collections.container'));
            }).error(function(data) {
                alert('Something went wrong.');
            });
        });

        $('a.get-communities').on('click', function(e) {
            $('ul.container').empty();
            e.preventDefault();
            var dspaceUrl = $('#api-url').val();
            if (dspaceUrl == '') {
                alert('Try again with the dspace url');
                return;
            }
            var url = 'dspace-connector/index/fetch';
            $.ajax({
                'url'  : url,
                'data' : {'link' : 'communities', 'dspaceUrl' : dspaceUrl, 'expand' : 'collections' },
                'type' : 'get',
                'dataType' : 'json'
            }).done(function(data) {
                data = JSON.parse(data.data);
                data.forEach(writeCommunity);
            }).error(function(data) {
                alert('Something went wrong.');
            });
        });

        $('form').on('click', 'button.import-collection', function(e) {
            $('input.collection-link').prop('disabled', true);
            $('input.collection-name').prop('disabled', true);
            $(this).siblings('input.collection-link').prop('disabled', false);
            $(this).siblings('input.collection-name').prop('disabled', false);
        });
    });

    function writeCollection(collectionObj) {
        // this is the container to which to append the LI
        var template = $('tr.collection.template').clone();
        template.removeClass('template');
        template.find('td.name span.name').html(collectionObj.name);
        if (collectionObj.introductoryText == '') {
            template.find('td.description').html('No information provided');
        } else {
            template.find('td.description').html(collectionObj.introductoryText);
        }
        if (true) {
            template.addClass('in-community');
        }
        template.find('input.collection-link').val(collectionObj.link);
        template.find('input.collection-name').val(collectionObj.name);
        $('table#collections tbody').append(template);
    }

    function writeCommunity(communityObj) {
        var template = $('tr.community.template').clone();
        template.removeClass('template');
        template.find('th.name').html(communityObj.name);
        if (communityObj.introductoryText == '') {
            template.find('th.description').html('No information provided');
        } else {
            template.find('th.description').html(communityObj.introductoryText);
        }
        //$('ul.communities.container').append(template);
        $('table#collections tbody').append(template);
        //var container = template.find('.community-collections');
        communityObj.collections.forEach(writeCollection);
        
    }
})(jQuery);
