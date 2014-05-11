$(function() {
    var username = new LiveValidation('username', {
        validMessage: "Valid email address.",
        onlyOnBlur: false
    });
    username.add(Validate.Email);
    username.add(Validate.Length, {
        minimum: 10,
        maximum: 50
    });
})
