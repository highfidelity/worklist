
$(function() {

     Workitem.init();

});

var Workitem = {

    sandbox_url: '',

    init: function() {
        $("#view-sandbox").click(function() {
            if (repo_type == 'git') {
                window.open(sandbox_url, '_blank');
            } else {
                Workitem.openDiffPopup({
                    sandbox_url: sandbox_url,
                    workitem_id: workitem_id
                });
            }
        });
    },

    openDiffPopup: function(options) {
        if ($("#diffUrlDialog").length == 0) {
            $("<div id='diffUrlDialog' class='popup-body'><div class='content'>Loading ...</div></div>").appendTo("body");
            $("#diffUrlDialog").data("options", options);
            $('#diffUrlDialog').dialog({
                dialogClass: 'white-theme',
                title: 'View Sandbox Diff',
                autoOpen: false,
                closeOnEscape: true,
                resizable: false,
                width: '420px',
                show: 'drop',
                hide: 'drop',
                buttons: [
                    {
                        text: 'Ok',
                        click: function() {
                            if ($("#diffUrlDialog #diff-sandbox-url").length > 0) {
                                $("#diffUrlDialog").data("options", {
                                    sandbox_url: $("#diffUrlDialog #diff-sandbox-url").val(),
                                    workitem_id: $("#diffUrlDialog").data("options").workitem_id
                                });
                                Workitem.fillDiffPopup();
                            } else {
                                $(this).dialog("close");
                            }
                        }
                    }
                ],
                open: function() {
                    Workitem.fillDiffPopup();
                },
                close: function() {
                    $("#diffUrlDialog .content").html("");
                }
            });
        } else {
            $("#diffUrlDialog").data("options", options);
            $("#diffUrlDialog .content").html("");
        }
        $("#diffUrlDialog").dialog("open");
    },

    fillDiffPopup: function() {
        var options = $("#diffUrlDialog").data("options");
        $("#diffUrlDialog .content").load("api.php #urlContent", {
            action: 'workitemSandbox',
            method: 'getDiffUrlView',
            sandbox_url: options.sandbox_url,
            workitem_id: options.workitem_id
        });
    }


}
