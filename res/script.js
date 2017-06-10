jQuery(function ($) {
    $(".gallery-box").fancybox({
        slideShow: false,
        fullScreen: false,
        thumbs: false,
        afterMove: function (instance, item) {
            var downloadButton = $(instance.$refs.buttons).find('.hyflickr-download');
            if (downloadButton.length > 0) {
                downloadButton.attr('href', $(item.opts.$orig).attr('data-download'));
            } else {
                $('<a class="fancybox-button hyflickr-download" href="' + $(item.opts.$orig).attr('data-download') + '"></a>').prependTo(instance.$refs.buttons);
            }
        }
    });
});