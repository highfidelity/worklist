var Projects = {
    uploadedFile: null,
    filesUploading: false,

    init: function() {
        Projects.populateListing();
        $('#add-project').click(Projects.addProject);
    },

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
        project += '<li><a href="./jobs/' + json.name + '"><strong>' + json.bCount + ' jobs in bidding</strong></a></li>';
        project += '<li><a href="./jobs/' + json.name + '/done">' + json.cCount+ ' jobs completed</a></li>';
        project += '<li>$' + json.feesCount + ' spent</li>';
        project += '</ul>';
        project += '</article>';

        $('#projects').append(project);
    },

    addProject: function() {
        Utils.modal('addproject', {
            open: function(modal) {
                Projects.initFileUpload();
                $('form', modal).submit(function(event) {
                    event.preventDefault();
                    var name = new LiveValidation($('input[name="name"]', modal)[0], { onlyOnSubmit: true }),
                        description = new LiveValidation($('textarea[name="description"]', modal)[0], { onlyOnSubmit: true }),
                        githubRepoUrl = new LiveValidation($('input[name="repourl"]', modal)[0], { onlyOnSubmit: true }),
                        githubClientId =  new LiveValidation($('input[name="githubid"]', modal)[0], { onlyOnSubmit: true }),
                        githubClientSecret =  new LiveValidation($('input[name="githubsecret"]', modal)[0], { onlyOnSubmit: true });

                    name.add(Validate.Presence, { failureMessage: "Can't be empty!" });
                    name.add(Validate.Format, { failureMessage: 'Alphanumeric and dashes characters only', pattern: new RegExp(/^[-A-Za-z0-9]*$/) });
                    name.add(Validate.Format, { failureMessage: 'Must contain 1 alpha character at least', pattern: new RegExp(/^\d*[-a-zA-Z][-a-zA-Z0-9]*$/) });
                    name.add(Validate.Length, { minimum: 3, tooShortMessage: "Field must contain 3 characters at least!" } );
                    name.add(Validate.Length, { maximum: 32, tooLongMessage: "Field must contain 32 characters at most!" } );

                    description.add( Validate.Presence, { failureMessage: "Can't be empty!" });
                    var regex_url = /^(https?:\/\/)?([\da-z\.-]+)\.([a-z\.]{2,6})([\/\w \.-]*)*\/?$/;
                    githubRepoUrl.add(Validate.Format, { pattern: regex_url, failureMessage: "Repo URL is not valid" });

                    githubRepoUrl.add(Validate.Presence, { failureMessage: "Can't be empty!" });
                    githubClientId.add(Validate.Presence, { failureMessage: "Can't be empty!" });
                    githubClientSecret.add(Validate.Presence, { failureMessage: "Can't be empty!" });

                    if (!LiveValidation.massValidate([name, description, githubRepoUrl, githubClientId, githubClientSecret])) {
                        return false;
                    }

                    $.ajax({
                        url: './project/add/' + $('input[name="name"]', modal).val(),
                        dataType: 'json',
                        data: {
                            description: $('textarea[name="description"]', modal).val(),
                            website: $('input[name="website"]', modal).val(),
                            github_repo_url: $('input[name="repourl"]', modal).val(),
                            github_client_id: $('input[name="githubid"]', modal).val(),
                            github_client_secret: $('input[name="githubsecret"]', modal).val(),
                            logo: Projects.uploadedFile
                        },
                        type: 'POST',
                        success: function(data) {
                            if (data.success) {
                                window.location = './' + $('input[name="name"]', modal).val();
                            }
                        }
                    });

                    return false;
                });
            }
        })
        return false;
    },

    initFileUpload: function() {
        var options = {iframe: {url: './file/add'}};
        var zone = new FileDrop('projectlogoupload', options);

        zone.event('send', function (files) {
            files.each(function (file) {
                file.event('done', Projects.fileUploadDone);
                file.event('error', Projects.fileUploadError);
                file.sendTo('./file/add');
                Projects.filesUploading = true;
                Projects.animateUploadSpin();
            });
        });
        zone.multiple(false);

        $('#projectlogoupload > label > em').click(function() {
            $('#projectlogoupload input.fd-file').click();
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
                    $('#projectlogoupload > ul').html(parsed);
                    $('#projectlogoupload li[attachment=' + fileData.fileid + '] > i').click(Projects.removeFile);
                    Projects.uploadedFile = fileData.fileid;
                });
                Projects.fileUploadFinished();
            }
        });
    },

    fileUploadError: function(e, xhr) {
        Projects.fileUploadFinished();
    },

    fileUploadFinished: function() {
        Projects.filesUploading = false;
        Projects.stopUploadSpin();
    },

    removeFile: function(event) {
        var id = parseInt($(this).parent().attr('attachment'))
        $('#projectlogoupload li[attachment=' + id + ']').remove();
        Project.uploadedFile = null;
    },

    animateUploadSpin: function() {
        if ($('#projectlogoupload > .loading').length) {
            return;
        }
        $('<div>').addClass('loading').prependTo('#projectlogoupload');
        var target = $('#projectlogoupload > .loading')[0];
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
        $('#projectlogoupload > .loading').remove();
    }
}
