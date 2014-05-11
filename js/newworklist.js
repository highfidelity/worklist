var NewWorklist = {
    init: function() {
        // proxima-nova fonts
        if (typeof Typekit != 'undefined') {
            Typekit.load();
        }

        $(document).ajaxSend(function(event, request, settings) {
            if (settings.url == './status' && settings.type.toUpperCase() == 'POST') {
                return;
            }
            $('body').addClass('onAjax');
        });
        $(document).ajaxComplete(function() {
            $('body').removeClass('onAjax');
        });

        // Navbar responsiveness functions
        $('.navbar').affix({
            offset: {
            top: 1 // Set fix 1px before top
            }
        });

        $('.navbar').on('affix.bs.affix', function () {
            $('body').css('padding-top', $('.navbar').outerHeight() + 'px');
        });

        $('.navbar').on('affix-top.bs.affix', function () {
            $('body').css('padding-top', '0px');
        });
    }
}
