jQuery(function($){
    if (typeof wc_ratenkaufbyeasycredit_config === 'undefined') {
        return;
    }

    var prefix = 'wc_ratenkaufbyeasycredit_';
    var getBaseUrl = function(action) {
        return wc_ratenkaufbyeasycredit_config.url+'?action='+prefix+action;
    }

    $('#woocommerce_ratenkaufbyeasycredit_api_verify_credentials').click(function(){
        let button = $(this);
        button.prop('disabled', true);
        var apiKey = $('#woocommerce_ratenkaufbyeasycredit_api_key').val();
        var apiToken = $('#woocommerce_ratenkaufbyeasycredit_api_token').val();
        var apiSignature = $('#woocommerce_ratenkaufbyeasycredit_api_signature').val();

        $.getJSON(getBaseUrl('verify_credentials'),{api_key: apiKey, api_token: apiToken, api_signature: apiSignature},function(r){
            button.prop('disabled', false);
            alert(r.msg);
        });
    });

    var custom_uploader
        , targets = $('input[name$="_src"]')
    var showChosenImages = function(elems) {
        elems.each(function(index){
            var src = $(this).val();

            $(this).siblings('.ec-img').remove();
            $('<div class="ec-img"><img src="' + src + '"><a href="#void" class="ec-delete-img">Remove image</a><br><a href="#void" class="btn btn-primary ec-upload-img">Upload Image</a></div>').insertAfter($(this));
        });
    }

    showChosenImages(targets);

    $('.easycredit-marketing .form-table').on('click','.ec-upload-img', function (e) {
        e.preventDefault();

        var target = $(this).closest('.form-table').find('input[name$="_src"]');

        if (!custom_uploader) {
            custom_uploader = wp.media.frames.file_frame = wp.media({
                title: 'Choose Image',
                button: {
                    text: 'Choose Image'
                },
                multiple: false
            });
        }

        custom_uploader.off('select');
        custom_uploader.on('select', function() {
            attachment = custom_uploader.state().get('selection').first().toJSON();
            console.log(attachment);
            target.val(attachment.url);
            showChosenImages(target);
        });

        custom_uploader.open();
    });
    $('.easycredit-marketing .form-table').on('click','.ec-delete-img', function (e) {
        e.preventDefault();

        var target = $(this).closest('.form-table').find('input[name$="_src"]');
        target.val('');
        showChosenImages(targets);
    });

    var getTabs = function() {
        var tabs = document.querySelectorAll('.easycredit-marketing__tabs .easycredit-marketing__tab');

        return tabs;
    }
    var getTabContents = function() {
        var tabContents = document.querySelectorAll('.easycredit-marketing__tab-content');

        return tabContents;
    }
    var selectTab = function(target) {
        tabs = getTabs();
        tabContents = getTabContents();

        tabs.forEach(tab => {
            if ( $(tab).attr('data-target') === target ) {
                $(tab).addClass('active');
            } else {
                $(tab).removeClass('active');
            }
        });

        tabContents.forEach(content => {
            if ( $(content).attr('data-tab') === target ) {
                $(content).addClass('active');
            } else {
                $(content).removeClass('active');
            }
        });
    }
    var initTabs = function() {
        tabs = getTabs();

        tabs.forEach(tab => {
            $(tab).on('click', function (e) {
                target = $(this).attr('data-target');
                selectTab(target);
            });
        });
    }

    initTabs();
});
