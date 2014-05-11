
$(function() {
    var oldpasword = new LiveValidation('oldpassword');
    summary.add(Validate.Presence, {failureMessage: "You must enter the job title!"});
    var newpassword = new LiveValidation('newpassword',{onlyOnBlur: true });
    newpassword.add(Validate.Length, { minimum: 5, maximum: 255 } );
    var confirmpassword = new LiveValidation('confirmpassword');
    confirmpassword.add(Validate.Confirmation, { match: 'newpassword'} );

    $('form#password').submit(function(event) {
        console.log(LiveValidation.massValidate([oldpassword, newpassword, confirmpassword]));
        return false;
    })
})
