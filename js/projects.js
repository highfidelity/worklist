var Projects = {
    populateListing: function() {
        $.ajax({
            type: "GET",
            url: 'api.php?action=getProjects',
            dataType: 'json',
            success: function(json) {

                // Clear all contents on screen
                $('#projects').empty();

                for (var i = 0; i < json.length; i++) {
                    Projects.addProjectDetails(json[i]);
                }

                setTimeout(function() {
                    $('#projects').infinitescroll({
                        animate: true,
                        dataType: 'json',
                        debug: true,
                        appendCallback: false,
                        navSelector: '#page-nav',
                        nextSelector: '#page-nav a',
                        itemSelector: '#projects article',
                        extraScrollPx: 350,
                        loading: {
                            msgText: 'Loading the next set of projects...',
                            finishedMsg: 'No more pages to load'
                        }
                    }, function(json, opts) {
                        for (var i = 0; i < json.length; i++) {
                            Projects.addProjectDetails(json[i]);
                        }
                    });

                    // kill scroll binding
                    $(window).unbind('.infscr');
                }, 1);

                // remove the paginator when we're done.
                /*
                $(document).ajaxError(function(e, xhr, opt){
                    if (xhr.status == 404) {
                        $('a#next').remove();
                    }
                });
                */

            },
            error: function() {
                alert("error in populateListing");
            }
        })
    },

    addProjectDetails: function(json) {
        var project = '';
        var image_filename;
        var description = '';
        var link = '';

        link = encodeURIComponent(json.name);

        if (json.logo === null || json.logo === "") {
            image_filename = 'images/emptyLogo.png';
        } else {
            image_filename = 'uploads/' + json.logo;
        }

        if (json.description.length > 500) {
            description = json.description.substring(0, 500) + '... <a href="' + link + '">[ read more ]</a>';
        } else {
            description = json.description;
        }

        project += '<article id="project-' + json.project_id + '">';
        project += '<a href="' + link + '"><img src="' + image_filename + '" border="1" width="48" height="48"  title="Last commit: ' + json.last_commit + '" /></a>';

        project += '<h3><a href="' + link + '">' + json.name + '</a></h3>';
        project += '<section class="description">' + description + '</section>';
        project += '<ul class="stats">';
        project += '<li><a href="./jobs?status=bidding&project=' + json.name + '"><strong>' + json.bCount + ' jobs in bidding</strong></a></li>';
        project += '<li><a href="./jobs?status=completed&project=' + json.name + '">' + json.cCount+ ' jobs completed</a></li>';
        project += '<li>$' + json.feesCount + ' spent</li>';
        project += '</ul>';
        project += '</article>';

        $('#projects').append(project);
    },

    addProject: function() {
        $('#popup-addproject').dialog({
            autoOpen: false,
            dialogClass: 'white-theme',
            show: 'fade',
            hide: 'fade',
            maxWidth: 555,
            width: 555,
            resizable: false
        });
        $('#popup-addproject').data('title.dialog', 'Add Project');
        $('#popup-addproject').dialog('open');
        if (user_id) {
            // clear the form
            $('input[type="text"]', '#popup-addproject').val('');
            $('textarea', '#popup-addproject').val('');
            $('.LV_validation_message', '#popup-addproject').hide();

            // focus the submit button
            $('#save_project').focus();

            var optionsLiveValidation = { onlyOnSubmit: true };

            var project_name = new LiveValidation('name', optionsLiveValidation);
            var project_description = new LiveValidation('description', optionsLiveValidation);
            var github_repo_url = new LiveValidation('githubRepoURL', optionsLiveValidation),
                vGithubClientId =  new LiveValidation('githubClientId', optionsLiveValidation),
                vGithubClientSecret =  new LiveValidation('githubClientSecret', optionsLiveValidation);

            project_name.add( Validate.Presence, { failureMessage: "Can't be empty!" });
            project_name.add(Validate.Format, { failureMessage: 'Alphanumeric only', pattern: new RegExp(/^[A-Za-z0-9]*$/) });
            project_name.add(Validate.Length, { minimum: 3, tooShortMessage: "Field must contain 3 characters at least!" } );
            project_name.add(Validate.Length, { maximum: 32, tooLongMessage: "Field must contain 32 characters at most!" } );

            project_description.add( Validate.Presence, { failureMessage: "Can't be empty!" });
            var regex_url = /^(https?:\/\/)?([\da-z\.-]+)\.([a-z\.]{2,6})([\/\w \.-]*)*\/?$/;
            github_repo_url.add(Validate.Format, { pattern: regex_url, failureMessage: "Repo URL is not valid" });
            var gitHubValidator = [Validate.Presence, { failureMessage: "Can't be empty!" }];

            github_repo_url.add(gitHubValidator[0], gitHubValidator[1]);
            vGithubClientId.add(gitHubValidator[0], gitHubValidator[1]);
            vGithubClientSecret.add(gitHubValidator[0], gitHubValidator[1]);

            $('#checkGitHub').unbind('click').click( function () {
                // if checked
                if (this.checked) {
                    $('#github-info').show();
                    github_repo_url.enable();
                } else {
                    $('#github-info').hide();
                    github_repo_url.disable();
                }
            });

            $('#checkDefaultGitHub').unbind('click').click( function () {
                // if checked
                if (this.checked) {
                    $('#custom-github-info').hide();
                    vGithubClientId.disable();
                    vGithubClientSecret.disable();
                } else {
                    $('#custom-github-info').show();
                    vGithubClientId.enable();
                    vGithubClientSecret.enable();
                }
            });

            $('#cancel').click(function() {
                $('#popup-addproject').dialog('close');
            });

            $('#save_project').click(function() {
                var validateFields = new Array(
                    project_name,
                    project_description,
                    github_repo_url,
                    vGithubClientId,
                    vGithubClientSecret
                );

                $(this).attr('disabled', 'disabled');
                if (!LiveValidation.massValidate(validateFields)) {
                    $(this).removeAttr('disabled');
                    $(".error-submit").css('display', 'block');
                    $("#name_container span.LV_validation_message").css('margin-top', '-70px');
                    $("#name_container span.LV_validation_message").css('margin-bottom', '55px');
                    descriptionHeight = parseInt($("#description").css('height'));
                    marginTop = descriptionHeight + 51;
                    marginBottom = descriptionHeight + 37;
                    $("#description_container span.LV_validation_message").css('margin-top', '-' + marginTop + 'px');
                    $("#description_container span.LV_validation_message").css('margin-bottom', marginBottom + 'px');
                    $("#github_container span.LV_validation_message").css('margin-top', '-68px');
                    $("#github_container span.LV_validation_message").css('margin-left', '160px');
                    $("#github_container span.LV_validation_message").css('margin-bottom', '54px');
                    return false;
                }
                var addForm = $("#popup-addproject");
                $.ajax({
                    url: 'api.php',
                    dataType: 'json',
                    data: {
                        action: 'addProject',
                        name: $(':input[name="name"]', addForm).val(),
                        description: $(':input[name="description"]', addForm).val(),
                        logo: $(':input[name="logoProject"]', addForm).val(),
                        website: $(':input[name="website"]', addForm).val(),
                        checkGitHub: $(':input[name="checkGitHub"]', addForm).prop('checked'),
                        github_repo_url: $(':input[name="githubRepoURL"]', addForm).val(),
                        defaultGithubApp: $('#checkDefaultGitHub').is(':checked'),
                        githubClientId: $('#githubClientId').val(),
                        githubClientSecret: $('#githubClientSecret').val()
                    },
                    type: 'POST',
                    success: function(json){
                        if ( !json || json === null ) {
                            alert("json null in addproject");
                            $(this).removeAttr('disabled');
                            return;
                        }
                        if ( json.error ) {
                            alert(json.error);
                        } else {
                            $('#popup-addproject').dialog('close');
                            window.location.href = worklistUrl + 'projectStatus?project=' + $(':input[name="name"]', addForm).val();
                            return;
                        }
                    }
                });

                $(this).removeAttr('disabled');
                return false;
            });

            new AjaxUpload('projectLogoAdd', {
                action: 'jsonserver.php',
                name: 'logoFile',
                data: {
                    action: 'logoUpload',
                    projectid: '',
                },
                autoSubmit: true,
                responseType: 'json',
                onSubmit: validateUploadImage,
                onComplete: function(file, data) {
                    $('span.LV_validation_message.upload').css('display', 'none').empty();
                    if (!data.success) {
                        $('span.LV_validation_message.upload').css('display', 'inline').append(data.message);
                    } else if (data.success == true) {
                        $("#projectLogo").addClass('no-border');
                        $("#projectLogo").attr("src", data.url);
                        $('input[name=logoProject]').val(data.fileName);
                    }
                }
            });
        } else {
            $('#signup').click(function() {
                document.location = './signup';
            });
        }
    }

}

$(function() {
    Projects.populateListing();
    $('#add-project').click(Projects.addProject);
});

