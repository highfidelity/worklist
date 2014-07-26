var AddJob = {
    submitIsRunning: false,
    uploadedFiles: [],
    filesUploading: 0,

    init: function() {
        $('select[name="itemProject"], select[name="itemStatus"], select[name="assigned"]').chosen({
            width: '100%',
            disable_search_threshold: 10
        });
        $('select[name="itemProject"]').change(AddJob.showProjectDescription);
        $('select[name="assigned"]').change(AddJob.checkAssignedUser);

        $('form#addJob').submit(AddJob.formSubmit);

        AddJob.initFileUpload();
    },

    showProjectDescription: function() {
        $.ajax({
            url: 'project/info/' + $("select[name='itemProject']").val(),
            dataType: "json",
            success: function(json) {
                if (!json.success) {
                    return;
                }
                $("select[name='itemProject']").parent().next().text(json.data.short_description);
            }
        });
    },

    checkAssignedUser: function() {
        if (parseInt($(this).val()) > 0) {
            $("input[name='is_internal']").prop('checked', true);
            $("select[name='itemStatus']").val('Bidding');
            $("select[name='itemStatus']").trigger("chosen:updated");
        }
    },

    initFileUpload: function() {
        var options = {iframe: {url: './file/add'}};
        var zone = new FileDrop('attachments', options);

        zone.event('send', function (files) {
            files.each(function (file) {
                file.event('done', AddJob.fileUploadDone);
                file.event('error', AddJob.fileUploadError);
                file.sendTo('./file/add');
                AddJob.filesUploading++;
                AddJob.animateUploadSpin();
            });
        });
        zone.multiple(true);

        $('#attachments > label > em').click(function() {
            $('#attachments input.fd-file').click();
        })
    },

    fileUploadDone: function(xhr) {
        var fileData = $.parseJSON(xhr.responseText);
        $.ajax({
            url: './file/scan/' + fileData.fileid,
            type: 'POST',
            dataType: "json",
            success: function(json) {
                if (json.success == true) {
                    fileData.url = json.url;
                }
                Utils.parseMustache('partials/upload-document', fileData, function(parsed) {
                    $('#attachments > ul').append(parsed);
                    $('#attachments li[attachment=' + fileData.fileid + '] > i').click(AddJob.removeFile);
                    AddJob.uploadedFiles.push(fileData.fileid);
                });
                AddJob.fileUploadFinished();
            }
        });

    },

    fileUploadError: function(e, xhr) {
        AddJob.fileUploadFinished();
    },

    fileUploadFinished: function() {
        AddJob.filesUploading--;
        if (AddJob.filesUploading == 0) {
            AddJob.stopUploadSpin();
        }
    },

    removeFile: function(event) {
        var id = parseInt($(this).parent().attr('attachment'))
        $('#attachments li[attachment=' + id + ']').remove();
        for (var i = 0; i < AddJob.uploadedFiles.length; i++) {
            if (AddJob.uploadedFiles[i] == id) {
                AddJob.uploadedFiles.splice(i, 1);
            }
        }
    },

    animateUploadSpin: function() {
        if ($('#attachments > .loading').length) {
            return;
        }
        $('<div>').addClass('loading').prependTo('#attachments');
        var target = $('#attachments > .loading')[0];
        var spinner = new Spinner({
            lines: 9,
            length: 3,
            width: 4,
            radius: 6,
            corners: 1,
            rotate: 12,
            direction: 1,
            color: '#000',
            speed: 1.1,
            trail: 68
          }).spin(target);
    },

    stopUploadSpin: function() {
        $('#attachments > .loading').remove();
    },

    formSubmit: function(event){
        event.preventDefault();
        if (AddJob.submitIsRunning) {
            return false;
        }
        AddJob.submitIsRunning = true;

        var summary = new LiveValidation('summary');
        summary.add(Validate.Presence, {failureMessage: "You must enter the job title!"});

        var itemProject = new LiveValidation('itemProjectCombo');
        itemProject.add(Validate.Exclusion, {
            within: ['select'],
            partialMatch: true,
            failureMessage: "You have to choose a project!"
        });

        massValidation = LiveValidation.massValidate([itemProject, summary]);

        if (!massValidation) {
            AddJob.submitIsRunning = false;
            return false;
        }
        var skills = '';
        $('#labels li input[name^="label"]').each(function() {
            if ($(this).is(':checked')) {
                skills += (skills.length ? ', ' : '') + $(this).val();
            }
        });

        $.ajax({
            url: './job/add',
            dataType: 'json',
            data: {
                summary: $("input[name='summary']").val(),
                is_internal: $("input[name='is_internal']").is(':checked') ? 1 : 0,
                files: $("input[name='files']").val(),
                notes: $("textarea[name='notes']").val(),
                page: $("input[name='page']").val(),
                project_id: $("select[name='itemProject']").val(),
                status: $("select[name='itemStatus']").val(),
                skills: skills,
                fileUpload: {uploads: AddJob.uploadedFiles},
                assigned: $('select[name="assigned"]').val()
            },
            type: 'POST',
            success: function(json) {
                AddJob.submitIsRunning = false;
                if (json.error) {
                    alert(json.error);
                } else {
                    location.href = './' + json.workitem;
                }
            }
        });
        return false;
    },

    addLabel: function() {
        var currentLabels = ($('#labels').attr('val') ? $('#labels').attr('val') : '').split(',');
        var newLabels = $('#labels + input').val().split(',');
        var labels = $.unique($.merge(newLabels, currentLabels));
        var html = '', val = '';
        for (var i = 0; i < labels.length; i++) {
            if (!labels[i].trim().length) continue;
            html += '<li> ' + labels[i] + '</li>';
            val += (val.length ? ',' : '') + labels[i];
        }
        $('#labels').attr('val', val).html(html);
        $('#labels + input').val('');
    }
}
