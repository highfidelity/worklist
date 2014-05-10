//  Copyright (c) 2009, LoveMachine Inc.
//  All Rights Reserved.
//  http://www.lovemachineinc.com
var smsCountry = '';
var smsProvider = '';
var is_chrome = navigator.userAgent.toLowerCase().indexOf('chrome') > -1;
var twilio_countries = [];

function smsAddressVisibility(e)
{
    $('#countrynotsupported').remove();
    if ( $("#int_code option:selected").val() != '' ) {
        if (isTwilioSupported($("#int_code option:selected").val())) {
            $("#smsaddr").hide();
            $("#sms-other p").hide();
            $("#sms-other").hide();
            $("#phone").removeAttr('disabled');
            $("#send-test").show();
            $('#phone_prefix').text('+' + $("#int_code option:selected").val());
            $('#phone_wrapper').addClass('active');
        } else {
            $("#sms-other").show();
            $("#sms-other p").show();
            $("#smsaddr").show();
            $("#phone").attr('disabled', 'disabled');
            $("#send-test").hide()
              .after('<p id="countrynotsupported" class="LV_invalid">Sorry, this country is not currently supported for SMS. Enter an e-mail address below to receive text messages.</p>');
            $('#phone_prefix').text('');
            $('#phone_wrapper').removeClass('active');
        }
    }
}

function smsRefreshIntCode(init)
{
    var pos=4;
    if (!init) $("#phone_edit").val('1');
    if (smsCountry != $("#country").val()) {
        smsCountry = $("#country").val();
        if (smsCountry == '--') {
            $("#sms-other").hide();
        } else {
            var country = $("#country option:selected").text();
            var len = country.length;
            $("#int-code option").each(function(){
                if ($(this).text().trim().substr(0, len) == country) {
                    $(this).prop('selected', true);
                    return false;
                }
            });
        }
    }
    if ($("#country").val() == '--') {
        $("#sms-other").show();
    }

}

function smsUpdatePhone(filter)
{
    $("#phone_edit").val('1');
    var phone = $("#phone").val();
    if (filter) {
        $("#phone").val(phone.replace(/\D/g,''));
    }
}

/*
 * function sets into a cookie the time zone offset
 */
function get_timezone()
{
    var d = new Date()
    var value = -d.getTimezoneOffset()/60;        //gets the time zone
    //changes the value format to the used one
    if (value > 9) {
        value = '+' + value + '00';
    } else if (value > -1) {
        value = '+0' + value + '00';
    } else if (value > -10) {
        value = '-0' + Math.abs(value) + '00';
    } else {
        value += '00';
    }

    return value;
}

function loadTwilioCountriesList(fAfter) {
    $.ajax({
        url: 'api.php',
        type: 'post',
        data: {'action': 'getTwilioCountries'},
        dataType: 'json',
        success: function(json) {
            if (!json || json === null || !json.success) {
                return;
            }
            twilio_countries = json.list;

            if (fAfter) {
                fAfter();
            }
        },
    });
}
function isTwilioSupported(country_code) {
    var supported = false;
    for(var key in twilio_countries) {
        if (twilio_countries[key] == country_code) {
            supported = true;
            break;
        }
    }
    return supported;
}

$(document).ready(function(){

    $("#phone").blur(function() { smsUpdatePhone(true); });
    $("#phone").keypress(function() { smsUpdatePhone(false); });
    $("#phone").watermark('Number (4155551212, e.g.)', {useNative: false});
    $("#country, #provider, #smsaddr").change(function() {
        smsRefreshIntCode();
        smsAddressVisibility();
    });

    loadTwilioCountriesList(function() {
        $("#int_code").change(function() {
            smsAddressVisibility();
        });
        smsRefreshIntCode(true);
        smsAddressVisibility();
    });

    if (!$('#timezone option[selected = "selected"]').length) {
        $('#timezone option[value = "' + get_timezone() + '"]').attr('selected','selected');
    }

});
