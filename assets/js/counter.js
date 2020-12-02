(function (window, $) {
    /* global wpApiSettings, viewscounter */

    'use strict'

    let el = $('#views_count > span')
    if (el.length) {
        if (viewscounter.post_id && viewscounter.allow) {
            let endpoint = wpApiSettings.root + 'counter/v1/views/' + viewscounter.post_id;
            $.ajax({
                url: endpoint,
                method: 'POST',
                beforeSend: function (xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', wpApiSettings.nonce);
                },
            }).done(function (response) {
                // обновление значения кол-ва просмотров
                el.html(response.data.views)
            }).fail(function (response) {
                console.log(response);
            })
        }
    }

})(window, jQuery)