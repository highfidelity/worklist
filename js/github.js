var GitHub = {
    isConnected: false,
    token: false,
    applicationKey: false,

    validate: function() {
        return GitHub.isConnected;
    },

    handleUserConnect: function(autoclose) {
        var client_id = GitHub.applicationKey;
        var redirect_uri = worklistUrl + 'github/connect?job=' + workitem_id;
        var url = 'https://github.com/login/oauth/authorize?client_id=' + client_id + '&scope=repo&redirect_uri=' + redirect_uri;
        window.location = url;
    }
};
