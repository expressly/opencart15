(function () {
    popupContinue = function () {
        var host = window.location.origin,
            parameters = window.location.search,
            uuid;

        parameters = parameters.split('&');

        for (var parameter in parameters) {
            if (parameters[parameter].indexOf('uuid') != -1) {
                uuid = parameters[parameter].split('=')[1];
            }
        }

        window.location.replace(host + '?route=expressly/migrate/complete&uuid=' + uuid);
    };

    popupClose = function () {
        window.location.replace(window.location.origin);
    };

    openTerms = function (event) {
        console.log(event);
    };

    openPrivacy = function (event) {
        console.log(event);
    };
})();