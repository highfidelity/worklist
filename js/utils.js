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
    relativeTime: function(time, withIn, justNow, withAgo) {
        var secs = Math.abs(time);
        var mins = 60;
        var hour = mins * 60;
        var day = hour * 24;
        var week = day * 7;
        var month = day * 30;
        var year = day * 365;
        segments = {}
        segments.yr = parseInt(secs / year);
        secs %= year;
        segments.mnth = parseInt(secs / month);
        secs %= month;
        if (!segments.yr) {
            segments.day = parseInt(secs / day);
            secs %= day;
            if (!segments.mnth) {
                segments.hr = parseInt(secs / hour);
                secs %= hour;
                if (!segments.day) {
                    segments.min = parseInt(secs / mins);
                    secs %= mins;
                    if (!segments.hr && !segments.min) {
                        segments.sec = secs;
                    }
                }
            }
        }
        var relTime = '';
        for (unit in segments) {
            var cnt = segments[unit];
            if (cnt) {
                relTime += cnt + ' ' + unit;
                if (cnt > 1) {
                    relTime += 's';
                }
                relTime += ', ';
            }
        }
        relTime = relTime.substr(0, relTime.length - 2);
        if (relTime) {
            withAgo = typeof(withAgo) == 'undefined' ? true : withAgo;
            withIn = typeof(withIn) == 'undefined' ? true : withIn;
            return (time < 0) ? (withAgo ? '' : '-') + (relTime + (withAgo ? ' ago' : '')) : (withIn ? 'in ' + relTime : relTime);
        } else {
            justNow = typeof(justNow) == 'undefined' ? true : justNow;
            return justNow ? 'just now' : '';
        }
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

