$(document).ready(function(){

    var autoArgs = autocompleteMultiple('getskills', skillsSet);
    $("#skills").bind("keydown", autoArgs.bind);
    $("#skills").autocomplete(autoArgs);

});
