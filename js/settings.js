var nclass;
var nickname, username, about, paypal, firstname, lastname, w9_accepted;

function completeUploadImage(file, data) {
    $('span.LV_validation_message.upload').css('display', 'none').empty();
    if (!data.success) {
        $('span.LV_validation_message.upload').css('display', 'inline').append(data.message);
    } else {
        window.location.reload();
    }
}

function validateNames(file, extension) {
    if (LiveValidation.massValidate( [ firstname, lastname ] )) {
        return validateW9Upload(file, extension);
    } else {
        return false;
    }
}

function validateW9Upload(file, extension) {
    nclass = '.uploadnotice-w9';
    return validateUpload(file, extension);
}
function validateUpload(file, extension) {
    if (! (extension && /^(pdf)$/i.test(extension))) {
        // extension is not allowed

        // Restore the styling of upload button
        $('#formupload').attr('value', 'upload W9');
        $('#formupload').removeClass('w9_upload_disabled');
        $('.w9_loader').css('visibility', 'hidden');

        $(nclass).empty();
        var html = '<div class="ui-state-error ui-corner-all">' +
                        '<p style="margin: 0;"><span style="float: left; margin-right: 0.3em;" class="ui-icon ui-icon-alert"></span>' +
                        '<strong>Error:</strong> This filetype is not allowed. Please upload a pdf file.</p>' +
                    '</div>';
        $(nclass).append(html);
        // cancel upload
        return false;
    }else{
        // Inform the user that the file is being uploaded...
        $(nclass).empty();
        $('#formupload').attr('value', 'uploading...');
        $('#formupload').addClass('w9_upload_disabled');
        $('.w9_loader').css('visibility', 'visible');
    }
}

function completeUpload(file, data) {
    $(nclass).empty();
    if (data.success) {
        // Restore the styling of upload button
        $('#formupload').attr('value', 'Success!');
        $('#formupload').removeClass('w9_upload_disabled');
        $('.w9_loader').css('visibility', 'hidden');

        var html = '<div style="padding: 0.7em; margin: 0.7em 0; width:285px;" class="ui-state-highlight ui-corner-all">' +
                        '<p style="margin: 0;"><span style="float: left; margin-right: 0.3em;" class="ui-icon ui-icon-info"></span>' +
                        '<strong>Info:</strong> ' + data.message + '</p>' +
                    '</div>';
        saveSettings();
    } else {
        // Restore the styling of upload button
        $('#formupload').attr('value', 'Fail');
        $('#formupload').removeClass('w9_upload_disabled');
        $('.w9_loader').css('visibility', 'hidden');

        var html = '<div style="padding: 0.7em; margin: 0.7em 0; width:285px;" class="ui-state-error ui-corner-all">' +
                        '<p style="margin: 0;"><span style="float: left; margin-right: 0.3em;" class="ui-icon ui-icon-alert"></span>' +
                        '<strong>Error:</strong> ' + data.message + '</p>' +
                    '</div>';
        this.enable();
    }
    $(nclass).append(html);
}

function validateW9Agree(value) {
    if (! $('#w9_accepted').is(':checked') && $('#country').val() == 'US') {
        return false;
    }
    return true;
}

function isJSON(json) {
    json = json.replace(/\\["\\\/bfnrtu]/g, '@');
    json = json.replace(/"[^"\\\n\r]*"|true|false|null|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?/g, ']');
    json = json.replace(/(?:^|:|,)(?:\s*\[)+/g, '');
    return (/^[\],:{}\s]*$/.test(json))
}
function saveSettings() {
    var values;
    var massValidation = LiveValidation.massValidate( [ nickname, username, paypal, w9_accepted ], true);
    if (massValidation) {
        values = {
            save: 1,
            timezone: $('#timezone').val(),
            country: $('#country').val(),
            bid_alerts: $('#bid_alerts').prop('checked') ? 1 : 0,
            nickname: $('#nickname').val(),
            username: $('#username').val(),
            self_email_notify: $('input[name="self_email_notify"]').prop('checked') ? 1 : 0,
            bidding_email_notify: $('input[name="bidding_email_notify"]').prop('checked') ? 1 : 0,
            review_email_notify: $('input[name="review_email_notify"]').prop('checked') ? 1 : 0,
            about: $("#about").val(),
            skills: $("#skills").val(),
            contactway: $("#contactway").val(),
            paypal_email: $("#paypal_email").val(),
            payway: $("#payway").val(),
            w9_accepted: $('#w9_accepted').is(':checked'),
            first_name: $("#first_name").val(),
            last_name: $("#last_name").val(),
        };
    } else {
        // Validation failed. We use openNotifyOverlay to display messages
        var errorHtml = createMultipleNotifyHtmlMessages(LiveValidation.massValidateErrors);
        openNotifyOverlay(errorHtml, null, null, true);
        return false;
    }

    $('.error').text('');

    $.ajax({
        type: "POST",
        url: './settings',
        data: values,
        success: function(json) {

            var message = 'Account settings saved!';
            var settings_json = isJSON(json) ? jQuery.parseJSON(json) : null;
            if (settings_json && settings_json.error) {
                console.log(settings_json);
                if (settings_json.error == 1) {
                    message = "There was an error updating your information.<br/>Please try again or contact an admin for assistance.<br/>Reason for failure: " + settings_json.message;
                } else {
                    message = json.message;
                }
            }

            if(settings_json.error == 1) {
                openNotifyOverlay(message,false,false,true); // Display with a red border id its an error
            } else {
                openNotifyOverlay(message);
            }

        },
        error: function(xhdr, status, err) {
            $('#msg-'+type).text('We were unable to save your settings. Please try again.');
        }
    });
}

$(function () {
    $('#timezone, #country').chosen();

    if (ppConfirmed || emConfirmed) {
        $('<div id="popup-confirmed"><div class="content"></div></div>').appendTo('body');

        if (ppconfirmed) {
            var $title = 'Your Paypal address was confirmed';
            var $content = 'Thank you for confirming your Paypal address.<br/><br/>You can now bid on items in the Worklist!<br/><br/><input style="" class="closeButton" type="button" value="Close" />';
        } else {
            var $title = 'Your email change is confirmed.';
            var $content = 'Thank you for confirming your changed email address.<br/><br/><input style="" class="closeButton" type="button" value="Close" />';
        }

        $('#popup-confirmed').dialog({
            dialogClass: "white-theme",
            modal: true,
            title: $title,
            autoOpen: true,
            width: 300,
            position: ['top'],
            open: function() {
                $('#popup-confirmed .content').html($content);
                $('#popup-confirmed .closeButton').click(function() {
                    $('#popup-confirmed').dialog('close');
                });
            }
        });
    }

    var uploadOptions = {
        action: 'api.php',
        name: 'profile',
        data: {
            action: 'uploadProfilePicture',
            api_key: uploadApiKey,
            userid: user_id
        },
        autoSubmit: true,
        hoverClass: 'imageHover',
        responseType: 'json',
        onSubmit: validateUploadImage,
        onComplete: completeUploadImage
    };

    new AjaxUpload('profilepicture', uploadOptions);
    new AjaxUpload('profilebutton', uploadOptions);

    new AjaxUpload('formupload', {
        action: 'jsonserver.php',
        name: 'Filedata',
        data: { action: 'w9Upload', userid: user_id },
        autoSubmit: true,
        responseType: 'json',
        onSubmit: validateNames,
        onComplete: completeUpload
    });

    $("#w9-dialog").dialog({
        dialogClass: 'white-theme',
        resizable: false,
        width: 220,
        title: 'W9 form upload',
        autoOpen: false,
        position: ['top'],
        open: function() {
            $("#last_name").val(lastName);
            $("#first_name").val(firstName);
            $(".uploadnotice-w9").html('');
            $(".LV_validation_message").html('');
        }
    });

    $("#uploadw9").click(function() {
        $("#w9-dialog").dialog("open");
     });

    $.ajax({
        type: "POST",
        url: 'jsonserver.php',
        data: {
            action: 'isUSCitizen',
            userid: user_id
        },
        dataType: 'json',
        success: function(data) {
            if ((data.success === true) && (data.isuscitizen === true)) {
            $('#w9upload').show();
            }
        }
    });
    $("#settings").submit(function(event) {
        event.preventDefault();
        saveSettings();
        return false;
    });

    nickname = new LiveValidation('nickname');
    nickname.add(Validate.Length, { minimum: 0, maximum: 25 } );
    nickname.add(Validate.Format, { pattern: /^[a-zA-Z0-9][a-zA-Z0-9_\-]*$/, failureMessage: "Must consist only of alphanumerics, underscores, or dash characters!" });
    nickname.add(Validate.Exclusion, { within: [ 'Nickname' ], failureMessage: "You must set your Nickname!" });

    username = new LiveValidation('username');
    username.add( Validate.Email );
    username.add(Validate.Length, { minimum: 4, maximum: 50 } );
    username.add(Validate.Exclusion, { within: [ 'username' ], failureMessage: "You must set your Email!" });

    about = new LiveValidation('about');
    about.add(Validate.Length, { minimum: 0, maximum: 150 } );

    paypal = new LiveValidation('paypal_email');
    paypal.add(Validate.Email);
    // TODO: Review requirements here. We let people signup without paypal, and we let them delete their paypal
    // email, which removes their paypal verification and prevents them from bidding
    // paypal.add(Validate.Presence, { failureMessage: "Can't be empty!" });

    firstname = new LiveValidation('first_name', {onlyOnBlur: true});
    firstname.add(Validate.Presence, { failureMessage: "Sorry, we need your first name before you can upload your W9. It’s only for administrative purposes and won’t be displayed in your profile"});
    firstname.add(Validate.Format, { pattern: /^[a-zA-Z]+$/, failureMessage: "Only characters through a-z and A-Z are allowed" });

    lastname = new LiveValidation('last_name', {onlyOnBlur: true});
    lastname.add(Validate.Presence, { failureMessage: "Sorry, we need your last name before you can upload your W9. It’s only for administrative purposes and won’t be displayed in your profile"});
    lastname.add(Validate.Format, { pattern: /^[a-zA-Z]+$/, failureMessage: "Only characters through a-z and A-Z are allowed" });

    w9_accepted = new LiveValidation('w9_accepted', {insertAfterWhatNode: 'w9_accepted_label'});
    w9_accepted.displayMessageWhenEmpty = true;
    w9_accepted.add(Validate.Custom, { against: validateW9Agree, failureMessage: "Oops! You forgot to agree that you'd keep us posted if you move. Please check the box, it's required, thanks!" });

});
