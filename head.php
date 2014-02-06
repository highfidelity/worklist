<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="copyright" content="Copyright (c) 2012 CoffeeandPower Inc.  All Rights Reserved. http://www.worklist.net" />
    <link href="css/common.css" rel="stylesheet" type="text/css" />
    <link href="css/menu.css" rel="stylesheet" type="text/css" />
    <link href="css/CMRstyles.css" rel="stylesheet" type="text/css" />
    <link href="css/LVstyles.css" rel="stylesheet" type="text/css" />
    <link href="css/lightbox-hc.css" rel="stylesheet" type="text/css" />
    <link href="css/jquery.combobox.css" rel="stylesheet" type="text/css" />
    <link rel="shortcut icon" type="image/x-icon" href="images/worklist_favicon.png" />
    <link media="all" type="text/css" href="css/jquery-ui.css" rel="stylesheet" />
    <link rel="stylesheet" type="text/css" href="css/smoothness/lm.ui.css"/>
    <link rel="stylesheet" type="text/css" href="css/smoothness/white-theme.lm.ui.css"/>
    <link rel="stylesheet" type="text/css" href="css/tooltip.css" />
    <link rel="stylesheet" href="css/font-awesome/css/font-awesome.min.css">
    <link href="css/responsive.css" rel="stylesheet" type="text/css" />
    <!--[if IE 7]>
    <link rel="stylesheet" href="css/font-awesome/css/font-awesome-ie7.min.css">
    <![endif]-->
    <!--[if IE 6]>
    <link rel="stylesheet" href="css/ie.css" type="text/css" media="all" />
    <![endif]-->
    
    <script type="text/javascript" src="js/jquery-1.7.1.min.js"></script>
    <script type="text/javascript" src="js/jquery-ui-1.8.12.min.js"></script>
    <script type="text/javascript" src="js/jquery.watermark.min.js"></script>
    <script type="text/javascript" src="js/jquery.livevalidation.js"></script>
    <script type="text/javascript" src="js/lightbox-hc.js"></script>
    <script type="text/javascript" src="js/class.js"></script>
    <script type="text/javascript" src="js/plugins/jquery.scrollTo-min.js"></script>
    <script type="text/javascript" src="js/jquery.combobox.js"></script>
    <script type="text/javascript" src="js/jquery.autogrow.js"></script>
    <script type="text/javascript" src="js/common.js"></script>
    <script type="text/javascript" src="js/plugins/jquery.tooltip.min.js"></script>
    <script type="text/javascript" src="js/utils.js"></script>
    <script type="text/javascript" src="js/userstats.js"></script>
    <script type="text/javascript" src="js/worklist.js"></script>
    <script type="text/javascript" src="js/budget.js"></script>    
    <script type="text/javascript">
        var user_id = <?php echo isset($_SESSION['userid']) ? $_SESSION['userid'] : 0; ?>;
        var worklistUrl = '<?php echo SERVER_URL; ?>';
        var sessionusername = '<?php echo $_SESSION['username']; ?>';

        $(function () {
            // initialize growing textareas
            $("textarea[class*=expand]").autogrow();
            // @TODO: This only needs to run on certain pages, settings -- lithium
            $('#username').watermark('Email address', {useNative: false});
            $('#password').watermark('Password', {useNative: false});
            $('#oldpassword').watermark('Current Password', {useNative: false});
            $('#newpassword').watermark('New Password', {useNative: false});
            $('#confirmpassword').watermark('Confirm Password', {useNative: false});
            $('#nickname').watermark('Nickname', {useNative: false});
            $('#about').watermark('Tell us about yourself', {useNative: false});
            $('#contactway').watermark('Skype, email, phone, etc.', {useNative: false});
            $('#payway').watermark('Paypal, check, etc.', {useNative: false});
            $('.skills-watermark').watermark('Your skills', {useNative: false});
            $('#findus').watermark('Google, Yahoo, others..', {useNative: false});
            $('#phoneconfirmstr').watermark('Phone confirm string', {useNative: false});
            // @TODO: This looks specific to masspay -- lithium
            $('#pp_api_username').watermark('API Username', {useNative: false});
            $('#pp_api_password').watermark('API Password', {useNative: false});
            $('#pp_api_signature').watermark('API Signature', {useNative: false});

            if ($('#fees-week').length > 0) {
                $('#fees-week').parents("tr").click(function() {
                    var author = "Guest";
                    if($('#user').length > 0) {
                        author = $('#user').html();
                    }
                    var t = 'Weekly fees for '+author;
                    $('#wFees').dialog({
                        autoOpen: false,
                        title: t,
                        dialogClass: 'white-theme',
                        show: 'fade',
                        hide: 'fade'
                    });
                    $('#wFees').dialog( "option", "title", t );
                    $('#wFees').addClass('table-popup');
                    $('#wFees').html('<img src="images/loader.gif" />');
                    $('#wFees').dialog('open');
                    $.getJSON('api.php?action=getFeeSums&type=weekly', function(json) {
                        if (json.error == 1) {
                            $('#wFees').html('Some error occured or you are not logged in.');
                        } else {
                          $('#wFees').html(json.output);
                        }
                    });
                });
            }

            if($('#fees-month').length > 0){
                $('#fees-month').parents("tr").click(function() {
                    var author = "Guest";
                    if ($('#user').length > 0) {
                        author = $('#user').html();
                    }
                    var t = 'Monthly fees for '+author;
                    $('#wFees').dialog({
                        autoOpen: false,
                        title: t,
                        dialogClass: 'white-theme',
                        show: 'fade',
                        hide: 'fade'
                    });
                    $('#wFees').dialog("option", "title", t);
                    $('#wFees').addClass('table-popup');
                    $('#wFees').html('<img src="images/loader.gif" />');
                    $('#wFees').dialog('open');
                    $.getJSON('api.php?action=getFeeSums&type=monthly', function(json) {
                        if (json.error == 1) {
                            $('#wFees').html('Some error occured or you are not logged in.');
                        } else {
                            $('#wFees').html(json.output);
                        }
                    });
                });
            }

            $('a.feesum').tooltip({
                delay: 300,
                showURL: false,
                fade: 150,
                bodyHandler: function() {
                    return $($(this).attr("href")).html();
                },
                positionLeft: false
            });

            $('#user-info').dialog({
               autoOpen: false,
               resizable: false,
               modal: false,
               show: 'fade',
               hide: 'fade',
               width: 840,
               height: 480
            });
        });

        var updateFeeSumsTimes = setInterval(function () {
            $.get('api.php?action=getFeeSums', function(data) {
                var sum = eval('('+data+')');
                if (typeof sum != 'object') {
                    return false;
                }
                $('#fees-week').html ('$'+sum.week);
                $('#fees-month').html ('$'+sum.month);
            });
        }, <?php echo AJAX_REFRESH * 1000; ?>);
    </script>
    <!--  tooltip plugin and dictionary -->
    <script type="text/javascript">
        function MapToolTips() {
            var tooltipPhraseBook = <?php echo $tooltip ?>;
            $.each(tooltipPhraseBook, function(k,v) {
                $('.iToolTip.' + k).attr('title', v);
            });
            $('.iToolTip.hoverJobRow').each(function(a,b) {
                var jobId = $(this).attr('id');
                var jobIdNum = jobId.substring(jobId.lastIndexOf('-') + 1, jobId.length);
                var tit = tooltipPhraseBook.hoverJobRow;
                $(this).attr('title',(tit + ' #' + jobIdNum));
            });
            $('.iToolTip').tooltip({
                track: false,
                delay: 600,
                showURL: false,
                showBody: " - ",
                fade: 150,
                positionLeft: true
            });
        };
    </script>