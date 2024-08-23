import { Controller } from "@hotwired/stimulus";
import $ from 'jquery';

import 'select2';
import 'select2/dist/js/i18n/de.js';

import '@ttskch/select2-bootstrap4-theme/dist/select2-bootstrap4.min.css'

//import 'mark.js';
//import 'mark.js/dist/jquery.mark.js';

export default class extends Controller {
    PAGINATION_LIMIT = 10;

    static values = {
        remoteTarget: String,
        placeholder: String,
    }

    connect() {
        const $element = $(this.element);
        const $parentModal = $element.parents("div.modal-content").first();

        // config
        const placeholder = this.placeholderValue || 'Suchen...';
        const url = this.remoteTargetValue;

        // endpoint
        let format = undefined;
        let result = this._processResult();
        switch (url) {
            case '/api/users':
                format = this._formatUserState;
                result = this._processResult('nickname');
                break;
            case '/api/clans':
                format = this._formatClanState;
                result = this._processResult('name');
                break;
        }

        const ajax = (!url) ? undefined : {
            url: url,
            delay: 700,
            dataTable: 'json',
            data: (params) => {
                // Query parameters will be ?search=[term]&page=[page]
                return {
                    q: params.term,
                    page: params.page || 1,
                    limit: this.PAGINATION_LIMIT,
                };
            },
            processResults: result,
        };

        $element.select2({
            placeholder: placeholder,
            language: 'de',
            theme: 'bootstrap4',
            allowClear: true,
            minimumInputLength: 2,
            dropdownParent: $parentModal.length ? $parentModal : $(document.body),
            dropdownAutoWidth: true,
            templateResult: format,
            ajax: ajax,
            width: '100%',
        });
    }

    _processResult(text = "text", id = "uuid") {
        return (data, params) => {
            params.page = params.page || 1;
            return {
                results: $.map(data.items, (val) => {
                    return {
                        id: val[id],
                        text: val[text],
                        val: val
                    };
                }),
                pagination: {
                    more: (params.page * this.PAGINATION_LIMIT) < data.total
                }
            };
        };
    }

    _formatClanState(item) {
        let $render = $('<div>');
        if (item.val && item.val.clantag && item.val.name) {
            $render.append($('<span class="px-2 badge badge-primary badge-pill">').text(item.val.clantag));
            $render.append($('<span class="px-2">').text(item.val.name));
        } else {
            $render.text(item.text);
        }
        return $render;
    }

    _formatUserState(item) {
        let $render = $('<div>');
        if (item.val && item.val.nickname && item.val.firstname && item.val.surname) {
            $render.append($('<strong>').text(item.val.nickname));
            $render.append($('<span class="px-2">').text(item.val.firstname + ' ' + item.val.surname));
            $render.append('<br />');
            $render.append($('<small>').text(item.val.email));
        } else {
            $render.text(item.text);
        }
        return $render;
    }
}
