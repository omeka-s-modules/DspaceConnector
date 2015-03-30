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
                data.forEach(writeCollectionLi, $('ul.collections.container'));
                

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
                data.forEach(writeCommunityLi);
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

    function writeCollectionLi(collectionObj) {
        // this is the container to which to append the LI
        var template = $('li.collection.template').clone();
        template.removeClass('template');
        template.find('label').html(collectionObj.name);
        if (collectionObj.introductoryText == '') {
            template.find('p.field-comment').html('No information provided');
        } else {
            template.find('p.field-comment').html(collectionObj.introductoryText);
        }
        
        template.find('input.collection-link').val(collectionObj.link);
        template.find('input.collection-name').val(collectionObj.name);
        this.append(template);
        //@TODO avoid this duplication from globals
        template.find('.o-icon-info').on('mouseover', function(e) {
            var moreInfoIcon = $(this);
            var fieldDesc = moreInfoIcon.next('.field-comment');
            var fieldDescBottom = moreInfoIcon.offset().top + moreInfoIcon.height() + fieldDesc.height() - $(window).scrollTop();
            if (fieldDescBottom > $(window).height()) {
                fieldDesc.removeClass('below').addClass('above');
            } else {
                fieldDesc.removeClass('above').addClass('below');
            }
        });
    }

    function writeCommunityLi(communityObj) {
        var template = $('li.community.template').clone();
        template.removeClass('template');
        template.find('label').html(communityObj.name);
        if (communityObj.introductoryText == '') {
            template.find('p.field-comment').html('No information provided');
        } else {
            template.find('p.field-comment').html(communityObj.introductoryText);
        }
        
        var container = template.find('.community-collections');
        communityObj.collections.forEach(writeCollectionLi, container);
        $('ul.communities.container').append(template);
        //@TODO avoid this duplication from globals
        template.find('.o-icon-info').on('mouseover', function(e) {
            var moreInfoIcon = $(this);
            var fieldDesc = moreInfoIcon.next('.field-comment');
            var fieldDescBottom = moreInfoIcon.offset().top + moreInfoIcon.height() + fieldDesc.height() - $(window).scrollTop();
            if (fieldDescBottom > $(window).height()) {
                fieldDesc.removeClass('below').addClass('above');
            } else {
                fieldDesc.removeClass('above').addClass('below');
            }
        });
    }
})(jQuery);
