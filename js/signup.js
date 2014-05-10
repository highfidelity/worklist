$(function() {
    $('#country, #timezone').chosen();

    var username = new LiveValidation('username', {onlyOnSubmit: true});
    username.add( Validate.Presence );
    username.add( Validate.Email );
    username.add(Validate.Length, { minimum: 4, maximum: 50 } );
    var password = new LiveValidation('password', {onlyOnSubmit: true});
    password.add(Validate.Presence);
    password.add(Validate.Length, { minimum: 5, maximum: 255 } );
    var confirmpassword = new LiveValidation('confirmpassword', {onlyOnSubmit: true});
    confirmpassword.add(Validate.Presence);
    confirmpassword.add(Validate.Length, { minimum: 5, maximum: 255 } );
    confirmpassword.add(Validate.Confirmation, {match: 'password'} );
    var nickname = new LiveValidation('nickname', {onlyOnSubmit: true});
    nickname.add( Validate.Presence );
    nickname.add(Validate.Length, { minimum: 3, maximum: 32 } );
    var about = new LiveValidation('about', {onlyOnSubmit: true});
    about.add(Validate.Length, { minimum: 0, maximum: 150 } );

    if (confirmTxt.length > 0) {
        Utils.emptyModal({
            title: 'Email confirmation',
            content: confirmTxt,
            close: function() {
                window.location = './';
            }
        });
    }
    $('#signupForm input[type="submit"]').submit(function(event) {
        console.log('a');
        if (!LiveValidation.massValidate([username, password, confirmpassword, nickname, about])) {
            event.preventDefault();
            return false;
        }
    });
});
