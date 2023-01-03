import $ from 'jquery';

import 'select2';
import 'select2/dist/js/i18n/de.js';

import '@ttskch/select2-bootstrap4-theme/dist/select2-bootstrap4.min.css'

import 'mark.js';
import 'mark.js/dist/jquery.mark.js';

$(document).on('select2:open', (event) => {
    const searchField = document.querySelector(
        `.select2-search__field[aria-controls="select2-${event.target.getAttribute('data-select2-id')}-results"]`,
    );
    if (searchField) {
        searchField.focus();
    }
});

(function ($, window, document, undefined) {
    let Select2Init = function (element, options) {
        this.element = element;

        let defaults = {
            remoteTarget: element.dataset.remoteTarget,
            placeholderLabel: element.dataset.label || 'User suchen...',
        };

        this.settings = $.extend({}, defaults, options);
        this._defaults = defaults;
        this._name = "Select2";
        this.init();
    };

    $.extend(Select2Init.prototype, {
        init: function () {
            const PAGINIATION_LIMIT = 10;
            let $target = $(this.element);
            let $modal = $(this.element).parents("div.modal-content").first();

            let $dropDownModal = "";
            if ($modal.length !== 0) {
                $dropDownModal = $modal;
            }

            $target.select2({
                placeholder: this.settings.placeholderLabel,
                language: 'de',
                theme: 'bootstrap4',
                allowClear: true,
                minimumInputLength: 2,
                width: '50%',  // hacky fix
                dropdownAutoWidth : true,
                dropdownParent: $dropDownModal,

                ajax: {
                    url: this.settings.remoteTarget,
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
            $target.closest('form').on('reset', () => {
                $target.val('').trigger('change');
            });
        }
    });

    $.fn.Select2 = function (options) {
        return this.each(function () {
            if (!$.data(this, "plugin_Select2")) {
                $.data(this, "plugin_Select2", new Select2Init(this, options));
            }
        });
    };
})(jQuery, window, document);
