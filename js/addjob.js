$(function() {
    var submitIsRunning;

    $("#bug_job_id").blur(function() {
        $("#bug_job_id").keyup();
    });

    //lookup and show job summary on bug_job_id change
    $("#bug_job_id").keyup(function() {
        var id=$("#bug_job_id").val();
        if(id.length) {
            $.ajax({
                url: 'api.php',
                dataType: 'json',
                data: {
                    action: 'getJobInformation',
                    itemid: id
                },
                type: 'POST',
                success: function(json) {
                    if (!json || json === null) {
                        alert("json null in getjobinformation");
                        return;
                    }
                    if (json.error) {
                        alert(json.error);
                    } else {
                        if(json.returnString.length > 0) {
                            $('#bug_job_id + p').html(json.returnString);
                            $("#bug_job_id + p").attr("bug-job-id" , id);
                            //Enable bug_job_id textbox
                            if (!/^(\[?)BUG/i.test($('#summary').val())) {
                                $('#summary').val('[BUG] ' + $('#summary').val().trim());
                            }
                        } else {
                            $('#bug_job_id + p').html("Item doesn't exist");
                            $("#bug_job_id + p").attr("bug-job-id" , 0);
                            if (/^\[BUG\]/i.test($('#summary').val())) {
                                $('#summary').val($('#summary').val().substring(5).trim());
                            }
                        }
                    }
                }
            });
        } else {
            if (/^\[BUG\]/i.test($('#summary').val())) {
                $('#summary').val($('#summary').val().substring(5).trim());
            }
        }
    });

    $('select[name="itemProject"]').chosen();
    $('select[name="status"]').chosen();

    var imageArray = new Array();
    var documentsArray = new Array();
    $('#addaccordion').fileUpload({images: imageArray, documents: documentsArray});

    var autoArgs = autocompleteMultiple('getuserslist', null);
    $("#invite").bind("keydown", autoArgs.bind);
    $("#invite").autocomplete(autoArgs);

    var autoArgsSkills = autocompleteMultiple('getskills', skillsSet),
        hasAutocompleter = false;
    $("#skills").bind("keydown", autoArgsSkills.bind);
    $("#skills").autocomplete(autoArgsSkills);
    hasAutocompleter = true;

    $('form#addJob').submit(function(event){
        event.preventDefault();
        if (submitIsRunning) {
            return false;
        }
        submitIsRunning = true;

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
            submitIsRunning = false;
            return false;
        }

        $.ajax({
            url: 'api.php',
            dataType: 'json',
            data: {
                action: 'addWorkitem',
                summary: $("input[name='summary']").val(),
                files: $("input[name='files']").val(),
                invite: $("input[name='invite']").val(),
                notes: $("textarea[name='notes']").val(),
                page: $("input[name='page']").val(),
                project_id: $("select[name='itemProject']").val(),
                status: $("select[name='status']").val(),
                skills: $("input[name='skills']").val(),
                bug_job_id: $("input[name='bug_job_id']").val(),
                fileUpload: $('#addaccordion').data('fileUpload')
            },
            type: 'POST',
            success: function(json) {
                submitIsRunning = false;
                if (json.error) {
                    alert(json.error);
                } else {
                    location.href = './' + json.workitem;
                }
            }
        });
        return false;
    });
});
