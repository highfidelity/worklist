(function($) {

    var methods = {
        init: function(options) {
            if(typeof(options) == 'undefined') {
                options = {};
            }
            return this.each(function() {
                var $this = $(this), fdata = $this.data('fileUpload');
                if(!fdata) {
                    fdata = {};
                    fdata.user = (typeof(user_id) != 'undefined') ? user_id : '';
                    fdata.workitem = (typeof(workitem_id) != 'undefined') ? workitem_id : null;
                    fdata.projectid = (typeof(projectid) != 'undefined') ? projectid : null;
                    fdata.images = (typeof(options.images) != 'undefined') ? options.images : new Array();
                    fdata.documents = (typeof(options.documents) != 'undefined') ? options.documents : new Array();
                    fdata.trackfiles = typeof(options.tracker) != 'undefined' ? options.tracker : false;
                    $this.data('fileUpload', fdata);
                }

                // initiate the upload
                var uploadButton = $this.next('.fileUploadButton, .uploadbutton, #uploadButtonDiv');

                new AjaxUpload(uploadButton, {
                    action: 'jsonserver.php',
                    name: 'file',
                    data: {
                        action: 'fileUpload',
                        userid: fdata.user,
                        projectid: fdata.projectid,
                        workitem: fdata.workitem
                    },
                    autoSubmit: true,
                    responseType: 'json',
                    onSubmit: function(file, extension) {
                        var fdata = $this.data('fileUpload');
                        if ($("#upload-scan-file").length == 0) {
                            $('<div id="upload-scan-file"><div class="content"></div></div>').appendTo('body');
                        }

                        $('#upload-scan-file').dialog({
                            dialogClass: 'white-theme',
                            modal: true,
                            title: null,
                            autoOpen: true,
                            width: 300,
                            resizable : false,
                            open: function() {
                                $('#upload-scan-file .content').text('Uploading attachment ...');
                            }
                        });

                        if(fdata.trackfiles) {
                            fdata.trackfiles.val(fdata.images.concat(fdata.documents).join(','));
                            $this.data('fileUpload', fdata);
                        }

                        $this.accordion('activate', false);
                        $('.uploadnotice').empty();
                        if (! (extension && !(/^(exe)$/i.test(extension)))){
                            openNotifyOverlay('This filetype is not allowed', false);
                            $('#upload-scan-file').dialog('close');
                            return false;
                        }
                        this.disable();
                    },
                    onComplete: function(file, data) {
                        var fdata = $this.data('fileUpload');

                        this.enable();
                        if (data.success == true) {
                            $('#upload-scan-file .content').html('<p>Antivirus scanning attachment ...</p>');
                            // we delay the virus scan so that IE9 does proper updating of the dialog above
                            setTimeout(function() {
                                $.ajax({
                                    url: "jsonserver.php",
                                    type: "POST",
                                    data: "action=scanFile&fileid=" + data.fileid,
                                    dataType: "json",
                                    success: function(json) {
                                        if (json.success == true) {
                                            data.url = json.url;
                                        }

                                        $('#upload-scan-file').dialog("close");

                                        if(fdata.trackfiles && fdata.trackfiles.val == '') {
                                            fdata.images = new Array();
                                            fdata.documents = new Array();
                                        }

                                        var newFile = $('#uploadDocument').parseTemplate(data);
                                        $('#attachments').append(newFile);

                                        if (data.filetype == 'image') {
                                            data.icon = 'images/icons/tiff.png';
                                        } else {
                                        }
                                        var files = $('#uploadPanel').data('files');
                                        if (data.filetype == 'image') {
                                            if(typeof(files) != "undefined") {
                                                files.images.push(data);
                                            } else {
                                                fdata.images.push(data.fileid);
                                            }
                                        } else {
                                            if(typeof(files) != "undefined") {
                                                files.documents.push(data);
                                            } else {
                                                fdata.documents.push(data.fileid);
                                            }
                                        }
                                        if(typeof(files) != "undefined") {
                                            var filesHtml = $('#uploadedFiles').parseTemplate(files);
                                            $('#uploadPanel').html(filesHtml);
                                            $('#uploadPanel').data('files', files);
                                            $('#accordion').fileUpload({images: files.images, documents: files.documents});
                                        }
                                        if(fdata.trackfiles) {
                                            fdata.trackfiles.val(fdata.images.concat(fdata.documents).join(','));
                                        }
                                        $this.data('fileUpload', fdata);
                                        $this.fileUpload('editable');

                                    }
                                });
                            }, 100);

                        } else {
                             $('#upload-scan-file .content').html('<p>' + data.message + '</p>');
                        }

                    }
                });

                // initial call
                $this.fileUpload('editable');
            });
        },//end init
        editable: function() {
            return this.each(function() {
                var $this = $(this),fdata = $this.data('fileUpload');
                if (fdata.user && $('.edittextarea').editable) {
                    $('.edittextarea').editable('jsonserver.php', {
                        indicator: 'Saving ...',
                        tooltip: 'Click to add/edit note ...',
                        placeholder: 'Click to add note',
                        type: 'text',
                        submit: 'OK',
                        submitdata: function(value, settings) {
                            return {
                                action: 'changeFileDescription',
                                fileid: this.id.replace('fileDesc_', ''),
                                userid: fdata.user
                            };
                        },
                        method: 'post'
                    });
                    $('.edittext').editable('jsonserver.php', {
                        indicator: 'Saving ...',
                        tooltip: 'Click to change the title ...',
                        placeholder: 'Add title',
                        type: 'text',
                        submit: 'OK',
                        submitdata: function(value, settings) {
                            return {
                                action: 'changeFileTitle',
                                fileid: this.id.replace('fileTitle_', ''),
                                userid: fdata.user
                            };
                        },
                        method: 'post'
                    });
                }
                $(".removeAttachment").unbind("click").click(function(){
                    var file_id = this.id.replace('fileRemoveAttachment_', ''),
                    oThis = this;

                    if (!confirm('Are you sure you want to remove attachment ' + $('#fileTitle_' + file_id).text() + '?')) {
                        return;
                    }
                    $.ajax({
                        url: "jsonserver.php",
                        type: "POST",
                        data: "action=fileRemove&fileid=" + file_id +"&userid="+fdata.user,
                        dataType: "json",
                        success: function(json){
                            if (json.success == true) {
                                var iPos = -1;

                                if (fdata.documents.length > 0) {
                                    for (var i=0; i < fdata.documents.length; i++) {
                                        if (fdata.documents[i] == file_id) {
                                            iPos = i;
                                            break;
                                        }
                                    }

                                    if (iPos != -1) {
                                        fdata.documents.splice(iPos,1);
                                    }
                                }

                                if (iPos == -1 && fdata.images.length > 0) {
                                    for (var ii = 0; ii < fdata.images.length; ii++) {
                                        if (fdata.images[ii] == file_id) {
                                            fdata.images.splice(ii, 1);
                                            break;
                                        }
                                    }
                                }

                                $this.data('fileUpload', fdata);

                                var files = $('#uploadPanel').data('files');
                                if(typeof(files) != "undefined") {
                                    iPos = -1;
                                    if (files.documents.length > 0) {
                                        for (var i=0; i < files.documents.length; i++) {
                                            if (files.documents[i].fileid == file_id) {
                                                iPos = i;
                                                files.documents.splice(iPos, 1);
                                                break;
                                            }
                                        }
                                    }
                                    if (iPos == -1 && files.images.length > 0) {
                                        for (var ii = 0; ii < files.images.length; ii++) {
                                            if (files.images[ii].fileid == file_id) {
                                                files.images.splice(ii, 1);
                                                break;
                                             }
                                        }
                                    }
                                    var filesHtml = $('#uploadedFiles').parseTemplate(files);
                                    $('#uploadPanel').html(filesHtml);
                                    $('#uploadPanel').data('files', files);
                                    $('#accordion').fileUpload({images: files.images, documents: files.documents});
                                };
                                if(typeof(files) == "undefined") {
                                    $(oThis).parent().remove()
                                }
                            } else {
                                alert(json.message);
                            }

                        }
                    });
                });

            });
        },//end editable
    }

    $.fn.fileUpload = function(method) {
        // Method calling logic
        if (methods[method]) {
            return methods[ method ].apply(this, Array.prototype.slice.call(arguments, 1));
        } else if (typeof method === 'object' || ! method) {
            return methods.init.apply(this, arguments);
        } else {
            $.error('Method ' +  method + ' does not exist on jQuery.fileUpload');
        }
    };

})(jQuery);
