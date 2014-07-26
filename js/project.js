var Project = {
    logoUploading: false,

    init: function() {
        $('.accordion').accordion({
            clearStyle: true,
            collapsible: true,
            active: true
        });

        $('#short_description').bind('keyup', 'keydown', function() {
            if ($(this).val().length > 100) {
                $(this).val($(this).val().substring(0, 100));
            } else {
                $('#charCount').text(100 - $(this).val().length);
            }
        }).trigger('keyup');

        // Validate review input
        // @TODO: The :checkbox selector is too broad, we might
        // have additional checkboxes in the future..   - lithium
        $('.code_review_chks, #cr_users_specified_field').change(function(){
            Project.validateCodeReviews(this);
        });

        Project.updateDesigners();

        $('#removerunner').click(Project.removeDesigner);

        Project.refreshCodeReviewers();

        $('#removecodereviewer').click(Project.removeCodeReviewer);

        makeWorkitemTooltip(".payment-joblink, .joblink");

        //derived from bids to show edit dialog when project owner clicks on a role <mikewasmike 16-jun-2011>
        $('tr.role').click(Project.showRoleInfoModal);

        $('#addrunner').click(Project.addDesignerModal);

        $('#addcodereviewer').click(Project.addCodeReviewerModal);

        if (edit_mode) {
            $('#cancel_project_edit').click(function() {
                location.href = './' + projectName + '?action=view';
                return false;
            });
        } else {
            $('#edit_project').click(function() {
                location.href = './' + projectName + '?action=edit';
                return false;
            });
        }

        $('#roles-panel button').click(Project.showAddRoleForm);

        if (edit_mode) {
            Project.initLogoUpload();
        }
    },

    initLogoUpload: function() {
        var options = {iframe: {url: './file/add/' + projectName}};
        var zone = new FileDrop('projectLogo', options);

        zone.event('send', function (files) {
            files.each(function (file) {
                file.event('done', Project.logoUploadDone);
                file.event('error', Project.logoUploadError);
                file.sendTo('./file/add/' + projectName);
                Project.logoUploading = true;
                Project.animateUploadSpin();
            });
        });
        zone.multiple(false);

        $('#projectLogo img').click(function() {
            $('#projectLogo input.fd-file').click();
        });
    },

    logoUploadDone: function(xhr) {
        var fileData = $.parseJSON(xhr.responseText);
        $.ajax({
            url: './file/scan/' + fileData.fileid,
            type: 'POST',
            dataType: "json",
            success: function(json) {
                if (!json.success) {
                    return;
                }
                $("#projectLogo img").attr("src", json.url);
                $('input[name=logoProject]').val(json.url);
                Project.logoUploadFinished();
            }
        });

    },

    logoUploadError: function(e, xhr) {
        Project.logoUploadFinished();
    },

    logoUploadFinished: function() {
        Project.logoUploading = false;
        Project.stopUploadSpin();
    },

    animateUploadSpin: function() {
        if ($('#projectLogo > .loading').length) {
            return;
        }
        $('<div>').addClass('loading').prependTo('#projectLogo');
        var target = $('#projectLogo > .loading')[0];
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
        $('#projectLogo > .loading').remove();
    },

    validateCodeReviews: function() {
        if (!$('#cr_anyone_field').is(':checked') && !$('#cr_3_favorites_field').is(':checked') &&
            !$('#cr_project_admin_field').is(':checked') && !$('#cr_job_runner_field').is(':checked') &&
            !$('#cr_users_specified_field').is(':checked')) {
            $('#cr_anyone_field').prop('checked', true);
            openNotifyOverlay('One selection must be checked', true);
        };
        if($(this).attr('id') == "cr_users_specified_field") {
            if($('#cr_users_specified_field').is(':checked')) {
                $('.code_review_chks').prop('checked', false);
            }
        } else if ($(this).is(':checked')) {
            $('#cr_users_specified_field').prop('checked', false);
        }
    },

    showAddRoleForm: function() {
        Utils.emptyFormModal({
            title: 'Add role',
            action: './' + projectName,
            content:
                '<div class="row">' +
                '  <div class="col-md-6">' +
                '    <label for="role_title">Role Title</label>' +
                '  </div>' +
                '  <div class="col-md-6">' +
                '    <input type="text" class="form-control" name="role_title" id="role_title">' +
                '  </div>' +
                '</div>' +
                '<div class="row">' +
                '  <div class="col-md-6">' +
                '    <label for="percentage">Percentage</label>' +
                '  </div>' +
                '  <div class="col-md-6">' +
                '    <div class="input-group">' +
                '      <input type="text" class="form-control" name="percentage" id="percentage">' +
                '      <span class="input-group-addon">%</span>' +
                '    </div>' +
                '  </div>' +
                '</div>' +
                '<div class="row">' +
                '  <div class="col-md-6">' +
                '    <label for="min_amount">Minimum Amount</label>' +
                '  </div>' +
                '  <div class="col-md-6">' +
                '    <div class="input-group">' +
                '      <input type="text" class="form-control" name="min_amount" id="min_amount">' +
                '      <span class="input-group-addon">USD</span>' +
                '    </div>' +
                '  </div>' +
                '</div>',
            buttons: [
                {
                    type: 'submit',
                    name: 'save_role',
                    content: 'Save',
                    className: 'btn-primary',
                    dismiss: false
                }
            ],
            open: function(modal) {
                $('form', modal).submit(function(e) {
                    // see http://regexlib.com/REDetails.aspx?regexp_id=318
                    // but without dollar sign 22-NOV-2010 <krumch>
                    var regex_amount = /^(\d{1,3},?(\d{3},?)*\d{3}(\.\d{0,2})?|\d{1,3}(\.\d{0,2})?|\.\d{1,2}?)$/;
                    var regex_percent = /^100$|^\d{0,2}(\.\d{1,2})?$/;
                    var min_amount = new LiveValidation($('input[name="min_amount"]', modal)[0], { onlyOnSubmit: true });
                    min_amount.add( Validate.Format, { pattern: regex_amount, failureMessage: "Invalid Input!" });
                    var percentage = new LiveValidation($('input[name="min_amount"]', modal)[0],{ onlyOnSubmit: true });
                    percentage.add( Validate.Presence, { failureMessage: "Can't be empty!" });
                    percentage.add( Validate.Format, { pattern: regex_percent, failureMessage: "Invalid Input!" });
                    var role_title = new LiveValidation($('input[name="role_title"]', modal)[0], { onlyOnSubmit: true});
                        role_title.add( Validate.Presence, { failureMessage: "Can't be empty!" });
                    var massValidation = LiveValidation.massValidate([min_amount, percentage, role_title]);
                    if (!massValidation) {
                        return false;
                    }
                });
            }
        });
        return false;
    },

    showRoleInfoModal: function() {
        $.metadata.setType("elem", "script");
        var roleData = $(this).metadata();
        Utils.emptyFormModal({
            title: 'Role Info',
            action: './' + projectName,
            content:
                '<input type="hidden" name="role_id" value="' + roleData.id + '" />' +
                '<div class="row">' +
                '  <div class="col-md-6">' +
                '    <label for="role_title">Role Title</label>' +
                '  </div>' +
                '  <div class="col-md-6">' +
                '    ' + roleData.role_title +
                '  </div>' +
                '</div>' +
                '<div class="row">' +
                '  <div class="col-md-6">' +
                '    <label for="role_title">Percentage</label>' +
                '  </div>' +
                '  <div class="col-md-6">' +
                '    ' + roleData.percentage + '%' +
                '  </div>' +
                '</div>' +
                '<div class="row">' +
                '  <div class="col-md-6">' +
                '    <label for="min_amount">Minimum Amount</label>' +
                '  </div>' +
                '  <div class="col-md-6">' +
                '    $' + roleData.min_amount +
                '  </div>' +
                '</div>',
            buttons: [
                {
                    type: 'submit',
                    name: 'delete_role',
                    content: 'Delete',
                    className: 'btn-primary',
                    dismiss: false
                },
                {
                    name: 'edit',
                    content: 'Edit',
                    className: 'btn-primary',
                    dismiss: false
                }
            ],
            open: function(modal) {
                $('button[name="edit"]', modal).click(function() {
                    $(modal).modal('hide');
                    Project.showEditRoleModal(roleData);
                    return false;
                });
            }
        });
    },

    showEditRoleModal: function(roleData) {
        Utils.emptyFormModal({
            action: './' + projectName,
            content:
                '<input type="hidden" name="role_id" value="' + roleData.id + '" />' +
                '<div class="row">' +
                '  <div class="col-md-6">' +
                '    <label for="role_title">Role Title</label>' +
                '  </div>' +
                '  <div class="col-md-6">' +
                '    <input type="text" class="form-control" name="role_title" id="role_title" value="' + roleData.role_title + '">' +
                '  </div>' +
                '</div>' +
                '<div class="row">' +
                '  <div class="col-md-6">' +
                '    <label for="percentage">Percentage</label>' +
                '  </div>' +
                '  <div class="col-md-6">' +
                '    <div class="input-group">' +
                '      <input type="text" class="form-control" name="percentage" id="percentage" value="' + roleData.percentage + '">' +
                '      <span class="input-group-addon">%</span>' +
                '    </div>' +
                '  </div>' +
                '</div>' +
                '<div class="row">' +
                '  <div class="col-md-6">' +
                '    <label for="min_amount">Minimum Amount</label>' +
                '  </div>' +
                '  <div class="col-md-6">' +
                '    <div class="input-group">' +
                '      <input type="text" class="form-control" name="min_amount" id="min_amount" value="' + roleData.min_amount + '">' +
                '      <span class="input-group-addon">USD</span>' +
                '    </div>' +
                '  </div>' +
                '</div>',
            buttons: [
                {
                    type: 'submit',
                    name: 'edit_role',
                    content: 'Save',
                    className: 'btn-primary',
                    dismiss: false
                }
            ],
            open: function(modal) {
                $('form', modal).submit(function(e) {
                    // see http://regexlib.com/REDetails.aspx?regexp_id=318
                    // but without dollar sign 22-NOV-2010 <krumch>
                    var regex_amount = /^(\d{1,3},?(\d{3},?)*\d{3}(\.\d{0,2})?|\d{1,3}(\.\d{0,2})?|\.\d{1,2}?)$/;
                    var regex_percent = /^100$|^\d{0,2}(\.\d{1,2})?$/;
                    var min_amount = new LiveValidation($('input[name="min_amount"]', modal)[0], { onlyOnSubmit: true });
                    min_amount.add( Validate.Format, { pattern: regex_amount, failureMessage: "Invalid Input!" });
                    var percentage = new LiveValidation($('input[name="min_amount"]', modal)[0],{ onlyOnSubmit: true });
                    percentage.add( Validate.Presence, { failureMessage: "Can't be empty!" });
                    percentage.add( Validate.Format, { pattern: regex_percent, failureMessage: "Invalid Input!" });
                    var role_title = new LiveValidation($('input[name="role_title"]', modal)[0], { onlyOnSubmit: true});
                        role_title.add( Validate.Presence, { failureMessage: "Can't be empty!" });
                    var massValidation = LiveValidation.massValidate([min_amount, percentage, role_title]);
                    if (!massValidation) {
                        return false;
                    }
                });
            }
        });
        return false;
    },

    updateDesigners: function() {
        $('#projectRunners tbody').html('Loading ...');
        $.ajax({
            type: 'post',
            url: './project/designers/' + projectName,
            dataType: 'json',
            success: function(data) {
                $('#projectRunners tbody').html('');
                if (data.success) {
                    designers = data.data.designers;
                    var html = '';
                    if (designers.length > 0) {
                        for(var i=0; i < designers.length; i++) {
                            var designer = designers[i];
                            html =
                                '<tr class="runner">' +
                                    (isCodeReviewAdmin ? '<td class="runnerRemove">' + (designer.owner ? '' : '<input type="checkbox" name="runner' + designer.id + '" />') + '</td>' : '') +
                                    '<td class="runnerName"><a href="./user/' + designer.id + '" >' + designer.nickname + '</a></td>' +
                                    '<td class="runnerJobCount">' + designer.totalJobCount + '</td>' +
                                    '<td class="runnerLastActivity">' + (designer.lastActivity ? designer.lastActivity : '') + '</td>' +
                                '</tr>'
                            $('#projectRunners tbody').append(html);
                        }
                    }
                }
            }
        });
    },

    refreshCodeReviewers: function() {
        $('#projectCodeReviewRights tbody').html('Loading ...');
        $.ajax({
            type: 'post',
            url: './project/codeReviewers/' + projectName,
            dataType: 'json',
            success: function(data) {
                $('#projectCodeReviewers tbody').html('');
                if (data.success) {
                    codeReviewers = data.data.codeReviewers;
                    var html = '';
                    if (codeReviewers.length > 0) {
                        for(var i=0; i < codeReviewers.length; i++) {
                            var codeReviewer = codeReviewers[i];
                            html =
                                '<tr class="codeReviewer">' +
                                    '<td class="codeReviewerName">' +
                                      (isCodeReviewAdmin ? '<input type="checkbox" class="wlcheckbox" id="codereviewer' + codeReviewer.nickname + '" name="codereviewer' + codeReviewer.nickname + '" />' : '') +
                                      '<label for="codereviewer' + codeReviewer.nickname + '"><a href="./user/' + codeReviewer.nickname + '" >' +
                                        codeReviewer.nickname +
                                      '</a></label>' +
                                    '</td>' +
                                '</tr>';
                            $('#projectCodeReviewers tbody').append(html);
                        }
                    }
                }
            }
        });
    },

    removeCodeReviewer: function(event) {
        var codeReviewers = [];
        $('#projectCodeReviewRights input[name^=codereviewer]:checked').each(function() {
            var codeReviewer = $(this).attr('name').substring(12);
            codeReviewers.push(codeReviewer);
        });
        $.ajax({
            type: 'post',
            url: './project/removeCodeReviewer/' + projectName + '/' + codeReviewers.join('/'),
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    Project.refreshCodeReviewers();
                }
            }
        });
        return false;
    },

    addDesignerModal: function() {
        Utils.emptyFormModal({
            content:
                '<div class="row">' +
                '  <div class="col-md-6">' +
                '    <label for="newdesigner">New Designer</label>' +
                '  </div>' +
                '  <div class="col-md-6">' +
                '    <input type="text" class="form-control" id="newdesigner" name="newdesigner">' +
                '  </div>' +
                '</div>',
            buttons: [
                {
                    type: 'submit',
                    name: 'adddesigner',
                    content: 'Add Designer',
                    className: 'btn-primary',
                    dismiss: false
                }
            ],
            open: function(modal) {
                $('input[name="newdesigner"]', modal).autocomplete({source: autocompleteUserSource});
                $('form', modal).submit(function() {
                    var designer = $('input[name="newdesigner"]', modal).val();
                    $.ajax({
                        type: 'post',
                        url: './project/addDesigner/' + projectName + '/' + designer,
                        dataType: 'json',
                        success: function(data) {
                            if (data.success) {
                                Project.updateDesigners();
                            }
                        }
                    });
                    $(modal).modal('hide');
                    return false;
                });
            }
        });
        return false;
    },

    removeDesigner: function() {
        var runners = [];
        $('#projectRunners input[name^=runner]:checked').each(function() {
            var runner = parseInt($(this).attr('name').substring(6));
            runners.push(runner);
        });
        $.ajax({
            type: 'post',
            url: './project/removeDesigner/' + projectName + '/' + runners.join('/'),
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    Project.updateDesigners();
                }
            }
        });
        return false;
    },

    addCodeReviewerModal: function() {
        Utils.emptyFormModal({
            content:
                '<div class="row">' +
                '  <div class="col-md-6">' +
                '    <label for="codereviewer">New Code Reviewer</label>' +
                '  </div>' +
                '  <div class="col-md-6">' +
                '    <input type="text" class="form-control" id="codereviewer" name="codereviewer">' +
                '  </div>' +
                '</div>',
            buttons: [
                {
                    type: 'submit',
                    name: 'addcodereviewer',
                    content: 'Add Code Reviewer',
                    className: 'btn-primary',
                    dismiss: false
                }
            ],
            open: function(modal) {
                $('input[name="codereviewer"]', modal).autocomplete({source: autocompleteUserSource});
                $('form', modal).submit(function(e) {
                    var user = $('input[name="codereviewer"]', modal).val();
                    $.ajax({
                        type: 'post',
                        url: './project/addCodeReviewer/' + projectid + '/' + user,
                        dataType: 'json',
                        success: function(data) {
                            Project.refreshCodeReviewers();
                        }
                    });
                    $(modal).modal('hide')
                    return false;
                });
            }
        });
    }
};
