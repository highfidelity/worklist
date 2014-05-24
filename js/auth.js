var Auth = {
    emailSubmit_running: false,
    countries: [],

    init: function() {
        $('form#auth').submit(Auth.emailSubmit);
    },

    emailSubmit: function(event) {
        event.preventDefault();
        if (Auth.emailSubmit_running) {
            return false;
        }
        Auth.emailSubmit_running = true;

        var email = new LiveValidation('username', {onlyOnSubmit: true});
        email.add(Validate.Email, {failureMessage: "You must enter a valid e-mail address!"});
        massValidation = LiveValidation.massValidate([email]);

        if (!massValidation) {
            Auth.emailSubmit_running = false;
            return false;
        }

        var username = $('form#auth input[name="username"]').val();
        $.ajax({
            url: './user/exists/' + username,
            dataType: 'json',
            type: 'GET',
            error: function() {
                Auth.emailSubmit_running = false;
            },
            success: function(data) {
                if (!data.success) {
                    Auth.emailSubmit_running = false;
                    return;
                }
                var modal = 'auth/' + (data.exists ? 'authorize' : 'signup');
                Utils.modal(modal, {
                    open: function(dialog) {
                        if (!data.exists) {
                            Auth.renderCountryList(dialog);
                        }
                        $('form .btn-primary', dialog).click(function() {
                            $('form', dialog).submit();
                        });
                        $('form', dialog).submit(function(event) {
                            return data.exists ? Auth.authorize(dialog) : Auth.signup(dialog);
                        });
                    },
                    close: function(dialog) {
                        Auth.emailSubmit_running = false;
                    }
                });
            }
        });
    },

    loadCountryList: function(fAfter) {
        $.ajax({
            url: './user/countries/all',
            dataType: 'json',
            type: 'GET',
            success: function(data) {
                Auth.countries = data;
                if (typeof fAfter == 'function') {
                    fAfter();
                }
            }
        });
    },

    renderCountryList: function(dialog) {
        if (!Auth.countries.length) {
            Auth.loadCountryList(function() {
                Auth.renderCountryList();
            });
            return;
        }
        $('select[name="country"] > option', dialog).remove();
        if (!(defaultCountry = $('form#auth input[name="location"]').val())) {
            defaultCountry = 'US';
        }
        var defaultUpperCase = defaultCountry.toUpperCase().trim();
        for (var i = 0; i < Auth.countries.length; i++) {
            var countryCode = Auth.countries[i].code;
            var countryName = Auth.countries[i].name;
            $('<option>')
                .attr({
                    value: countryCode,
                    selected: (countryCode.toUpperCase() == defaultUpperCase || countryName.toUpperCase() == defaultUpperCase)
                })
                .text(countryName)
                .appendTo('select[name="country"]', dialog);
        }
        if (!$('select[name="country"] > option[selected="selected"]', dialog).length) {
            $('select[name="country"] > option[value="US"]', dialog).attr({selected: true});
        }
        $('select[name="country"]', dialog).chosen();
    },

    authorize: function(dialog) {
        $.ajax({
            url: './github/authorize',
            dataType: 'json',
            type: 'POST',
            data: {
                access_token: $('form#auth input[name="access_token"]').val(),
                username: $('form#auth input[name="username"]').val(),
                password: $('input[name="password"]', dialog).val(),
            }, 
            success: function(data) {
                var password = new LiveValidation('pass1', {onlyOnSubmit: true});
                password.add(Validate.Custom, {
                    failureMessage: "Authentication failed! Please try again.",
                    against: function() {
                        return (data.success);
                    }
                });
                massValidation = LiveValidation.massValidate([password]);
                if (massValidation) {
                    // success log in, let's take it to the worklist
                    window.location.href = 'github/success';
                }

            }
        });
        return false;
    },

    signup: function(dialog) {
        var password = new LiveValidation('pass1', {onlyOnSubmit: true});
        password.add(Validate.Presence);
        password.add(Validate.Length, { minimum: 5, maximum: 255 } );
        var pass2 = new LiveValidation('pass2', {onlyOnSubmit: true});
        pass2.add(Validate.Presence);
        pass2.add(Validate.Length, { minimum: 5, maximum: 255 } );
        pass2.add(Validate.Confirmation, {match: 'pass1'} );

        if (LiveValidation.massValidate([password, pass2])) {
            $.ajax({
                url: './github/signup',
                dataType: 'json',
                type: 'POST',
                data: {
                    access_token: $('form#auth input[name="access_token"]').val(),
                    country: $('select[name="country"]', dialog).val(),
                    username: $('form#auth input[name="username"]').val(),
                    password: $('input[name="password"]', dialog).val(),
                    password2: $('input[name="password2"]', dialog).val(),
                }, 
                success: function(data) {
                    if (!data.success) {
                        alert(data.msg);
                        return;
                    }
                    $(dialog).modal('hide');
                    Utils.emptyModal({
                        title: 'Email confirmation', 
                        content: data.msg, 
                        close: function() {
                            window.location.href = './';
                        }
                    });
                }
            });
        }
        return false;
    }
};

