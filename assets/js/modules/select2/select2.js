import $ from 'jquery';

import 'select2';
import 'select2/dist/js/i18n/de.js';

import 'mark.js';
import 'mark.js/dist/jquery.mark.js';

export default function() {
    $('.select2-enable').each(function () {
        const PAGINIATION_LIMIT = 10;
        let $remoteUrl = $(this).attr('data-remote-target');
        let $target = $(this);

        $target.select2({
            placeholder: 'User suchen...',
            language: 'de',
            theme: 'bootstrap4',
            allowClear: true,
            minimumInputLength: 2,
            ajax: {
                url: $remoteUrl,
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
                        return {
                            id: val.uuid,
                            text: val.nickname,
                            user: val
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
                delay: 700
            },
            templateResult: function (item) {
                if (item.loading) {
                    return item.text;
                }
                
                //TODO: Make own rendering function
                let $render = $('<div>');
                let $title = $('<strong>').text(item.text);
                $render.append($title);
                
                if (item.user.firstname || item.user.surname) {
                    let $name = $('<span class="px-2">').text(item.user.firstname + ' ' + item.user.surname);
                    $render.append($name);
                }
                
                $render.append('<br />');
                
                let $mail = $('<small>').text(item.user.email);
                $render.append($mail);
                
                let searchTerm = $target.data("select2").dropdown.$search.val();
                $render.mark(searchTerm); 
                
                return $render;
            }
        });
        $target.closest('form').on('reset', () => { debugger; $target.val('').trigger('change'); });
    });
}
