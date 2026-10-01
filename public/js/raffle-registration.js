(function () {
    'use strict';

    var successNotice = document.querySelector('[data-cm-raffle-registration-success]');
    if (!successNotice) {
        return;
    }

    var redirectUrl = successNotice.getAttribute('data-cm-redirect-url');
    if (!redirectUrl) {
        return;
    }

    var redirectTimer = window.setTimeout(function () {
        window.location.assign(redirectUrl);
    }, 5000);

    var presentationLink = document.querySelector('[data-cm-raffle-presentation-link]');
    if (presentationLink) {
        presentationLink.addEventListener('click', function () {
            window.clearTimeout(redirectTimer);
        });
    }
}());
