require('select2');
require('select2/dist/css/select2.css');
require('select2-bootstrap4-theme/dist/select2-bootstrap4.min.css');
require('select2/dist/js/i18n/de.js');

export function init() {
    $('.select2-enable').each(function () {
        const PAGINIATION_LIMIT = 10;
        let remoteUrl = $(this).attr('data-remote-target');

        $(this).select2({
            placeholder: 'User suchen...',
            language: 'de',
            theme: 'bootstrap4',
            allowClear: true,
            minimumInputLength: 2,
            ajax: {
                url: remoteUrl,
                data: function (params) {
                    // Query parameters will be ?search=[term]&page=[page]
                    return {
                        q: params.term,
                        page: params.page || 1,
                        limit: PAGINIATION_LIMIT
                    };
                },
                processResults: function (data, params) {
                    params.page = params.page || 1;
                    let foo = $.map(data.items, (val) => {
                        let name = val.nickname;
                        if (val.firstname && val.surname) {
                            name = name + '(' + val.firstname + ' ' + val.surname + ')';
                        }
                        return {
                            id: val.uuid,
                            text: name,
                        };
                    });
                    return {
                        results: foo,
                        pagination: {
                            more: (params.page * PAGINIATION_LIMIT) < data.total
                        }
                    };
                },
                dataType: 'json',
                delay: 700,
                cache: true
            }
        });
    });
}
