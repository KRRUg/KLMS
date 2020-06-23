import $ from 'jquery';
import dTLang from '../../modules/dataTables/dataTables.js';
import './ajaxModal.js';

;
(function ($, window, document, undefined) {

    "use strict";

    let AdminDataTable = function (element, options) {
        this.element = element;

        let defaults = {
            remoteTarget: element.dataset.dataRemoteTarget
        };

        this.settings = $.extend({}, defaults, options);
        this._defaults = defaults;
        this._name = "AdminDataTable";
        this.init();
    };

    // Avoid Plugin.prototype conflicts
    $.extend(AdminDataTable.prototype, {
        init: function () {
            let dtOptions = {
                searchHighlight: true,
                language: dTLang
            };

            if (this.settings.remoteTarget) {
                dtOptions.ajax = {
                    url: this.remoteTarget,
                    dataSrc: ""
                };
            }

            this.$table = $(this.element).DataTable(dtOptions);
        },
        _enableTrigger() {
            $(this.element).children('a[data-render="modal"]').on(
                'click',
                this._loadRemoteModal().bind(this)
            );
        },
    });

    // A really lightweight plugin wrapper around the constructor,
    // preventing against multiple instantiations
    $.fn.AdminDataTable = function (options) {
        return this.each(function () {
            if (!$.data(this, "plugin_AdminDataTable")) {
                $.data(this, "plugin_AdminDataTable", new AdminDataTable(this, options));
            }
        });
    };

})(jQuery, window, document);