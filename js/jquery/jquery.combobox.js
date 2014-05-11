/*!
 * jQuery ComboBox Plugin v1.0
 * http://thomas.stachl.me/
 *
 * Copyright 2010, Thomas Stachl
 * Dual licensed under the MIT or GPL Version 2 licenses.
 * http://jquery.org/license
 *
 * @author Thomas Stachl <thomas@stachl.me>
 * @description replaces the combobox with a styleable equivalent
 * @requires jQuery 1.4.2, jQuery UI 1.8.1, Class 0.0.2, jQuery.ScrollTo 1.4.2
 *
 * Date: Fri May 21 04:11:00 2010 +0100
 * 
 * Change by LoveMachine multiple options
 */
(function($) {
    var ComboBox = Class.create({
        container: null,
        textbox: null,
        trigger: null,
        list: null,
        el: null,
        values: null,
        settings: {},
        showList: false,
        
        init: function(o,settings) {
            var oThis=$(o),
                cThis = this;
            // combobox original select box
            this.el = $(o);
            this.settings=settings;
            // combobox container
            this.container = $('<div/>');
            if (this.el.hasClass("divComboBox")) {
                this.textbox = $('<div style="float: left; position: relative; top: 10px; height: 0px;"></div><input type="text"/>');
            } else {
                // combobox textbox, will become an autocompletebox in a later version
                this.textbox = $('<input type="text"/>');
            }
            // combobox trigger (icon)
            this.trigger = $('<span/>');
            // combobox list
            this.list = $('<ul/>');
            // combobox values array
            this.values = [];
            if (this.el.prop("multiple") === true ){
                this.list.addClass("ui-combobox-list-multiple");
                this.el.bind({
                    'beforeshow newlist': function(e, o) {
                        $("li",o.list).each(function(){
                            $(this).html("<input type='checkbox' /><span class='checkboxLabel'>" + $(this).text() + "</span>");
                        });
                        /** Keep this if we need a button to close the list **/
                        $("li[val=CheckDone]",o.list).html("<input type='button' value='Done' style='display:none;' id='CheckDone'/>")
                            .unbind("mouseover");
                            
                         $(".checkboxLabel",o.list).click(function(event){
                            event.preventDefault();
                            event.stopPropagation();
                           $("input[type=checkbox]",o.list).each(function(){
                                if ($(this).parent().attr('val') === "ALL") {return;}
                                var oThis=this;
                                if ($(oThis).is(':checked') ) {
                                    if ($(this).val() !== "ALL") {
                                        $(oThis).parent().click();                                        
                                    }
                                }
                            });
                            setTimeout(function() {
                                $(event.currentTarget).prev().parent().click();
                                cThis._hideList();
                            },500);
                            return true;
                        });
                            
                         $("input[type=checkbox]",o.list).click(function(event){
                            $(event.currentTarget).data("clicked",true);
                        });
                        $("#CheckDone",o.list).unbind("click").click(function(){
                            cThis._hideList();
                        });
                    }
                })
            }
            // fire event initialized
            this.el.trigger('init', this);
            
            // bind click events
            this.container.click($.proxy(this.click, this));
            $(document).click($.proxy(this._outClick, this));
            // bind key events
/*
    Removed: bug with a textarea in the same page
            $(document).keydown($.proxy(this.keydown, this));
      */      
            // setup the combobox
            this.el.trigger('beforesetup', this);
            this._setup();
            this.el.trigger('aftersetup', this);
            // get the initial selected value
            this.el.trigger('beforeinitselect', this);
            this._initSelected();
            this.el.trigger('afterinitselect', this);
            // show the combobox
            this.el.trigger('beforeshow', this);
            this.show();
            this.el.trigger('aftershow', this);
        },
        _initSelected: function() {
            // get initial value of original selectbox
            if (this.el.prop("multiple") === true ){
                var ele = this.el.val(),
                    more="";
                if (ele && ele !== null) {
                    if (ele.length > 1) {
                        more = " +";
                    }
                    // write the text in the textbox
                    eleText = this.getItemByValue(ele[0]).text;
                    this.textbox.val(eleText+more);
                }
            } else {
                var ele = this.getItemByValue(this.el.val());
                if (ele && ele !== null) {
                    if (this.el.hasClass("divComboBox")) {
                        // write the text in the textbox
                        this.textbox.html($(ele).html());
                    } else {
                        // write the text in the textbox
                        this.textbox.val(ele.text);
                    }
                }
            }
        },
        _addClasses: function() {
            // add classes for the container and set it to display no
            this.container.addClass('ui-state-default ui-corner-all ui-combobox').css({
                display: 'none'
            });
            // add classes for the textbox and calculate the with by the original element
            this.textbox.addClass('ui-state-default ui-combobox-textbox').css({
                width: this.el.outerWidth() - 2
            });
            // add classes for the trigger, here we can change the icon
            this.trigger.addClass('ui-state-default ui-icon ui-icon-carat-1-s');
            // add classes for the list and calculate the width
            this.list.addClass('ui-combobox-list ui-corner-bottom').css({
                width: (this.el.outerWidth() + 27 - 2),
                "margin-top": "6px"
            });
            this.container.addClass(this.el.attr('id'));
            this.container.attr('id', 'container-' + this.el.attr('id'));
            this.list.addClass(this.el.attr('id') + 'List');
        },
        _setup: function() {
            // hide the original element (don't remove it or the handlers will be killed)
            this.el.hide();
            // add all classes
            this._addClasses();
            // get the original list values
            this._getListValues();
            // setup the textbox
            this._setupTextbox();
            // generate hover listeners and functions
            this._setupHover();
            // setup the new list
            this._setupList();
            // append all elements to the container
            this.container.append(this.textbox).append(this.trigger).append(this.list);
            // add the combobox to the dom
            this.el.after(this.container);
            
        },
        _setupTextbox: function() {
            // as we don't provide autocomplete at the moment simply deactivate the textbox
            this.textbox.attr('readonly', true);
            this.textbox.focus(function() {
                $(this).blur();
            });
        },
        _setupHover: function() {
            // add hover states
            this.container.hover(
                $.proxy(function() {
                    // the list is shown we don't need the hover effect
                    if (!this.showList) {
                        this.container.addClass('ui-state-hover');
                        this.textbox.addClass('ui-state-hover');
                        this.trigger.addClass('ui-state-hover');
                    }
                }, this),
                $.proxy(function() {
                    this.container.removeClass('ui-state-hover');
                    this.textbox.removeClass('ui-state-hover');
                    this.trigger.removeClass('ui-state-hover');
                }, this)
            );
        },
        _setupList: function() {
            // empty the list before rebuild it
            this.el.empty();
            // create the listitems for the new list
            $.each(this.values, $.proxy(function(i, v) {
                var listItem = $('<li />');
                listItem.text(v.text);
                listItem.attr('val', v.value);
                if (v.selected) {
                    listItem.addClass('ui-combobox-list-selected');    
                }
                listItem.hover(
                    function() {
                        $('.ui-state-hover').removeClass('ui-state-hover');
                        $(this).addClass('ui-state-hover');
                    }
                );
                listItem.click($.proxy(this._listItemClicked, this));
                this.list.append(listItem);
                
                // Update the original select with the new option
                var sel = '';
                if (v.selected) {
                    sel = 'selected="selected"';
                }
                var opt = '<option value="' + v.value + '"' + sel + '>' + v.text + '</option>';
                this.el.append(opt);
            }, this));
            this.list.hide();
        },
        _getListValues: function() {
            // get the list values of the original element
            $.each(this.el.children(), $.proxy(function(i, v) {
                if ($(v).attr('value') != "") {
                    this.values.push({
                        value: $(v).attr('value'),
                        text: $(v).text(),
                        selected: $(v).prop('selected')
                    });
                }
            }, this));
        },
        _listItemClicked: function(e) {
            var oThis=this;
            // list item was clicked now we select the value
            e.preventDefault();
            this.select($(e.currentTarget).attr('val'));
            if (this.el.prop("multiple") === true ){
                    // remove the selected class from the previous selected item
                if ($(e.currentTarget).attr('val') == "ALL") {
                    $("input[type=checkbox]",oThis.list).each(function(){
                        if ($(this).val() != "ALL") {
                            $(this).prop('checked', false);
                        }
                    });
                }
                if ($("input[type=checkbox]",e.currentTarget).data("clicked") ) {
                    $("input[type=checkbox]",e.currentTarget).data("clicked",false)
                    if ($("input[type=checkbox]",e.currentTarget).is(':checked') ) {
                        setTimeout(function(){
                            $("input[type=checkbox]",e.currentTarget).prop('checked', true);
                        },50);
                    } else {
                        setTimeout(function(){
                            $("input[type=checkbox]",e.currentTarget).prop('checked', false);
                        },50);
                    }
                } else {
                    if ($("input[type=checkbox]",e.currentTarget).is(':checked') ) {
                        $("input[type=checkbox]",e.currentTarget).prop('checked', false);
                    } else {
                        $("input[type=checkbox]",e.currentTarget).prop('checked', true);
                    }
                }
                if ($(e.currentTarget).attr('val') === "ALL") {
                    $("#CheckDone",oThis.list).click();
                }
                return false;
            }
            return true;
        },
        _outClick: function() {
            // if we click outside the list while open we want it to close
            if (this.list.is(":visible")) {
                this._hideList();
            }
        },
        _hideList: function() {
            this.el.trigger('listClose', this);
            this.list.hide();
            this.container.removeClass('ui-state-active');
            this.container.removeClass('ui-state-hover');
            this.showList = false;
            // fire the change event of the original element
            this.el.change();
        },
        setupNewList: function(l) {
            // before we can setup a new list we have to hide the current one
            this._hideList();
            // load the new list into values
            this.values = l;
            // empty current list
            this.list.empty();
            // empty the original list
            this.el.empty();
            // setup new list
            this._setupList();
            // trigger an event
            this.el.triggerHandler('newlist', this);
        },
        getItemByValue: function(v) {
            // get an item form this.values by value
            var el = $.grep(this.values, function(n, i) {
                return (n.value == v);
            });
            // we return only one (first) element
            return el[0];
        },
        keydown: function(e) {
            if (((e.keyCode == 40) || (e.keyCode == 38) || (e.keyCode == 13) || (e.keyCode == 9) || (e.keyCode == 27)) && (this.showList == true)) {
                e.preventDefault();
                if ((e.keyCode == 40) && (this.list.find('.ui-state-hover').next().length > 0)) {
                    this.list.find('.ui-state-hover').removeClass('ui-state-hover').next().addClass('ui-state-hover');
                } else if ((e.keyCode == 38) && (this.list.find('.ui-state-hover').prev().length > 0)) {
                    this.list.find('.ui-state-hover').removeClass('ui-state-hover').prev().addClass('ui-state-hover');
                } else if ((e.keyCode == 13) || (e.keyCode == 9)) {
                    this.list.find('.ui-state-hover').click();
                } else if (e.keyCode == 27) {
                    this._hideList();
                }
                this.list.scrollTo(this.list.find('.ui-state-hover'));
            }
        },
        click: function(e) {
            e.preventDefault();
            if (this.list.is(":visible")) {
                // if the list is visible we hide it, set showList to false and remove the active state from the container
                this._hideList();
            } else {
                // if the list is not visible we have to show it
                // get the current position of the container
                var pos = this.container.position();
                // set the list to the correct position
                this.list.css({
                    left: pos.left
                });
                this.showList = true;
                // show the list and scroll to the selected item
                this.list.show();
                if (this.list.find('.ui-combobox-list-selected').length > 0) {
                    this.list.scrollTo(this.list.find('.ui-combobox-list-selected').get(0));
                }
                this.list.find('.ui-combobox-list-selected').addClass('ui-state-hover');
                if (this.el.prop("multiple") === true ){
                    this.list.find('.ui-combobox-list-selected').each(function(){
                        $("input[type=checkbox]",this).prop('checked', true);
                    });
                }
                // add the active state class to the container
                this.container.addClass('ui-state-active');
                this.el.trigger('listOpen', this);
            }
            return false;
        },
        select: function(val, fireEvent) {
            var oThis=this;
            // get the complete element (text, value, selected state)
            val = this.getItemByValue(val);
            // if the value is not currently selected we have to select it
            if (this.el.prop("multiple") === true ){
                var currentSelectedVal = [],
                    firstValue = "",
                    firstValueHtml = "",
                    more = "";
                if (val.value == "CheckDone") {
                    return;
                }
                $.each(this.values, function(i, v){
                // find the element in our own list and select it
                    if (v.value == val.value && val.value != "ALL") {
                        if (v.selected == true) {
                            oThis.list.find('li[val=' + v.value + ']').removeClass('ui-combobox-list-selected');
                            v.selected = false;
                        } else {
                            // and add it to the currently selected
                            oThis.list.find('li[val=' + v.value + ']').addClass('ui-combobox-list-selected');
                            v.selected = true;
                        }
                    } else if (v.value == val.value && val.value == "ALL") {
                        v.selected = true;
                        oThis.list.find('.ui-combobox-list-selected').removeClass('ui-combobox-list-selected');
                            // and add it to the currently selected
                        oThis.list.find('li[val=' + v.value + ']').addClass('ui-combobox-list-selected');
                    } else if (val.value == "ALL") {
                        oThis.list.find('li[val=' + v.value + ']').removeClass('ui-combobox-list-selected');
                        v.selected = false;
                    } else if (v.value == "ALL" && val.value != "ALL") {
                        oThis.list.find('li[val=' + v.value + ']').removeClass('ui-combobox-list-selected');
                        v.selected = false;
                    }
                    if (v.selected == true) {
                        currentSelectedVal.push(v.value);
                        if (firstValue=="") {
                            eleText = oThis.getItemByValue(v.value).text;
                            firstValue = eleText;
                            firstValueHtml = $(oThis.getItemByValue(v.value)).html();
                        } else {
                            more = " +";
                        }
                    }                        
                });
                this.el.val(currentSelectedVal);
                // update the textbox with the correct text
                if (this.el.hasClass("divComboBox")) {
                    // write the text in the textbox
                    this.textbox.html(firstValueHtml + more);
                } else if (firstValue == "") {
                    this.textbox.val("All Status");
                } else {
                    // write the text in the textbox
                    this.textbox.val(firstValue + more);
                }
            } else {
                if (!val.selected) {
                    // find the element in our own list and select it, deselect the old value
                    $.each(this.values, function(i, v){
                        if (v.selected == true) {
                            v.selected = false;
                        }
                        if (v.value == val.value) {
                            v.selected = true;
                        }
                    });
                    // remove the selected class from the previous selected item
                    this.list.find('.ui-combobox-list-selected').removeClass('ui-combobox-list-selected');
                    // and add it to the currently selected
                    this.list.find('li[val=' + val.value + ']').addClass('ui-combobox-list-selected');
                    if (this.el.hasClass("divComboBox")) {
                        // write the text in the textbox
                        this.textbox.html(this.list.find('li[val=' + val.value + ']').html());
                    } else {
                    // update the textbox with the correct text
                        this.textbox.val(val.text);
                    }
                    // change the value of the original element
                    this.el.val(val.value);
                    // fire the change event of the original element
                    if (fireEvent !== false)  this.el.change();
                }
            }
        },
        show: function() {
            // show the new combobox
            this.container.css('display', 'inline-block');
        },
        val: function(param) {
            var oThis=this;
            this.container.click();
            this.list.find('.ui-combobox-list-selected[val!=ALL]').click().removeClass('ui-state-hover');
            $.each(param, function(i, v){
                oThis.list.find('li[val=' +v + ']').click();
            });
            this._outClick();
        }
    });
    
    $.fn.comboBox = function(settings) {
        if (settings && settings.action && settings.action == "val") {
            if ($(this).data('comboBox')) {
                $(this).data('comboBox').val(settings.param);
            }
            return $(this);
        }
        return this.each(function() {
            var oC = new ComboBox(this,settings);
            $(this).data("comboBox",oC);
        });
    }
})(jQuery);
