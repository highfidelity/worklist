/**
 * Worklist
 * Copyright (c) 2010 High Fidelity inc.
 * All rights reserved.
 */

var Utils = {
    mustaches: [],

    /**
     * Returns true when the passed mustache has already loaded,
     * false otherwise
     */
    mustacheLoaded: function(mustacheTemplate) {
        for(var i=0; i < Utils.mustaches.length; i++) {
            if (Utils.mustaches[i].name == mustacheTemplate) {
                return true;
            }
        }
        return false;
    },

    /**
     * Loads the mustache from the server and stores it locally
     */
    loadMustache: function(mustacheTemplate, fAfter) {
        if (Utils.mustacheLoaded(mustacheTemplate)) {
            return;
        }
        var tpath = './views/mustache/' + mustacheTemplate + '.mustache';
        $.get(tpath, function(template) {
            Utils.mustaches.push({name: mustacheTemplate, template: template});
            if (typeof fAfter == 'function') {
                fAfter(template);
            }
        }, 'html');
    },

    /**
     * Returns the mustache template if it's loaded,
     * otherwise will return an empty string
     */
    getMustache: function(mustacheTemplate) {
        for(var i=0; i < Utils.mustaches.length; i++) {
            if (Utils.mustaches[i].name == mustacheTemplate) {
                return Utils.mustaches[i].template;
            }
        }
        return '';
    },

    /**
     * Parses a mustache and calls to a specified callback
     * onced parsed if present
     */
    parseMustache: function(mustacheTemplate, data, fAfter) {
        var parsed = '';
        if (Utils.mustacheLoaded(mustacheTemplate)) {
            parsed = Mustache.render(Utils.getMustache(mustacheTemplate), data);
            if (typeof fAfter == 'function') {
                fAfter(parsed);
            }
            return;
        }
        Utils.loadMustache(mustacheTemplate, function(template) {
            parsed = Mustache.render(template, data);
            if (typeof fAfter == 'function') {
                fAfter(parsed);
            }
        });
    },

    modal: function(name, data) {
        // generates a random id for the new modal (will use it to be removed on close)
        var id = 'modal-' + parseInt(Math.random() * (9999 - 99) + 99);
        while ($('#' + id).length) {
            var id = 'modal-' + parseInt(Math.random() * (9999 - 99) + 99);
        }
        var defaults = {
            modal_id: id,
            title: '',
            buttons: [],
            open: function() {},
            close: function() {}
        };
        var settings = $.extend({}, defaults, data);
        // if no buttons arep provided, let's use an 'Ok' one by default
        if (settings.buttons.length == 0) {
            settings.buttons = [{
                content: 'Ok',
                className: 'btn-primary',
                dismiss: true
            }];
        }
        var path = 'partials/modal/' + name;
        Utils.parseMustache(path, settings, function(parsed) {
            $(parsed).appendTo('body');
            $('#' + id).on('shown.bs.modal', function() {
                if (typeof settings.open == 'function') {
                    settings.open(this);
                }
            });
            $('#' + id).on('hidden.bs.modal', function() {
                if (typeof settings.close == 'function') {
                    settings.close(this);
                }
                $(id).remove();
            });
            $('#' + id).modal('show');
        });
    },

    emptyModal: function(data) {
        Utils.modal('empty', data);
    },

    emptyFormModal: function(data) {
        Utils.modal('empty-form', data);
    },
    /**
     * Shows a info dialog with @message
     */
    infoDialog: function(title, message) {

        if ($("#dialog-info").length == 0) {
            $("<div id='dialog-info'><div class='content'></div></div>").appendTo("body");
            $('#dialog-info').dialog({
                autoOpen: false,
                closeOnEscape: true,
                resizable: false,
                dialogClass: 'white-theme',
                modal: true,
                show: 'drop',
                hide: 'drop',
                buttons: [
                    {
                        text: 'Ok',
                        click: function() {
                            $(this).dialog("close");
                        }
                    }
                ]
            });
        }

        $("#dialog-info").dialog({
            title: title
        });
        $("#dialog-info .content").html(message)
        $('#dialog-info').dialog('open');
    },

    /**
     * Shows a error dialog with @message
     */
    errorDialog: function(message) {
        // Add handler for the OK button
        $('#errorOkBtn').click(function() {
            Utils.closeDialog('error');
        });

        // Set message text
        $('#errorMsg').html(message);

        $('#dialog-error').dialog({
                                       autoOpen:false,
                                       closeOnEscape:true,
                                       resizable:false,
                                       show:'drop',
                                       hide:'drop'
                                      });
        $('#dialog-error').dialog('open');
    },

    /**
     * Opens @dialog
     */
    openDialog: function(dialog) {
        $('#dialog-' + dialog).dialog({
                                      closeOnEscape:true,
                                      resizable:false,
                                      show:'drop',
                                      hide:'drop'
                                      });
        $('#dialog-' + dialog).dialog('open');
    },

    /**
     * Closes @dialog
     */
    closeDialog: function(dialog) {
        $('#dialog-' + dialog).dialog('close');
        $('#' + dialog + 'OkBtn').unbind('click');
    },

    /**
     * Validate json returned from an ajax call,
     * returns true if succeded, or false plus a dialog
     * with the error message if not.
     */
    validateJson: function(json) {
        if (json === null) {
            Utils.errorDialog('Couldn\'t retrieve data from the server.');
        }
        if (!json.succeded) {
            // Show error dialog
            if (!json.message) {
                Utils.errorDialog(json);
            } else {
                Utils.errorDialog(json.message);
            }
            return false;
        }
        return true;
    },

    /**
     * Calculates the relative time
     */
    relativeTime: function(t) {
        var now = new Date();
        t = t.replace(/-/g, "/");
        var x = new Date(t);
        var days = (x - now) / 1000 / 60 / 60 / 24;
        var daysRound = Math.floor(days);
        var hours = (x - now) / 1000 / 60 / 60 - (24 * daysRound);
        var hoursRound = Math.floor(hours);
        var minutes = (x - now) / 1000 /60 - (24 * 60 * daysRound) - (60 * hoursRound);
        var minutesRound = Math.floor(minutes);
        var seconds = (x - now) / 1000 - (24 * 60 * 60 * daysRound) - (60 * 60 * hoursRound) - (60 * minutesRound);
        var secondsRound = Math.round(seconds);
        var sec = (secondsRound == 1) ? " second" : " seconds";
        var min = (minutesRound == 1) ? " minute" : " minutes";
        var hr = (hoursRound == 1) ? " hour and " : " hours and ";
        var dy = (daysRound == 1)  ? " day " : " days, "

        var dateFormated;
        if (daysRound < 0) {
            dateFormated = "<span class='overdue'>Overdue</span>";
            return dateFormated;
        }
        dateFormated = "In ";
        if (daysRound > 0) dateFormated += daysRound + dy;
        if (hoursRound > 0) dateFormated += hoursRound + hr;
        if (minutesRound > 0) dateFormated += minutesRound + min;

        return dateFormated;
    },

    /**
     * Calculates the relative time
     */
    age: function(t) {
        var now = new Date();
        t = t.replace(/-/g, "/");
        var x = new Date(t);
        var days = (now - x) / 1000 / 60 / 60 / 24;
        var daysRound = Math.floor(days);
        var hours = (now - x) / 1000 / 60 / 60 - (24 * daysRound);
        var hoursRound = Math.floor(hours);
        var minutes = (now - x) / 1000 /60 - (24 * 60 * daysRound) - (60 * hoursRound);
        var minutesRound = Math.floor(minutes);
        var seconds = (now - x) / 1000 - (24 * 60 * 60 * daysRound) - (60 * 60 * hoursRound) - (60 * minutesRound);
        var secondsRound = Math.round(seconds);
        var sec = (secondsRound == 1) ? " second" : " seconds";
        var min = (minutesRound == 1) ? " minute" : " minutes";
        var hr = (hoursRound == 1) ? " hour" : " hours";
        var dy = (daysRound == 1)  ? " day" : " days"

        var dateFormated = " ";
        var sep = ", ";

        if (daysRound > 0) dateFormated += daysRound + dy;
        if (hoursRound > 0) dateFormated += sep + hoursRound + hr;
        if (minutesRound > 0) dateFormated += sep + minutesRound + min;
        if (secondsRound > 0) dateFormated += sep + secondsRound + sec;

        return dateFormated;
    },

    /**
     * International phone number validation
     */
    validPhone: function(number) {
        number.replace('[\s_\.\()-]+', '');
        if (number.substr(0, 1) == '+') {
            number = number.substr(1);
        }
        var match = number.match('^[0-9]{6}[0-9]+$');
        return (match != null);
    },

    /**
     * E-mail address validation
     */
    validEmail: function (email) {
        var regex = /^([a-zA-Z0-9_\.\-\+])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/;
        return regex.test(email);
    },

    /**
     * Returns whether browser is in a mobile device
     */
    isMobile: function() {
        return (typeof window.orientation != 'undefined');
    }
};

