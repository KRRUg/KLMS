import $ from 'jquery';
import dTLang from '../../modules/dataTables/dataTables.js';
import './ajaxModal.js';
import '../..//modules/confirmModal/confirmModal.js';
;
(function ($, window, document, undefined) {

    "use strict";

    let AdminDataTable = function (element, options) {
        this.element = element;

        let defaults = {
            remoteTarget: element.dataset.remoteTarget
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
                    url: this.settings.remoteTarget,
                    dataFilter: function(data){
                        var json = jQuery.parseJSON( data );
                        json.recordsTotal = json.total;
                        json.recordsFiltered = json.total;
                        json.data = json.items;

                        return JSON.stringify( json ); // return JSON string
                    }
                };
                dtOptions.serverSide = true;
                dtOptions.processing = true;
            }
            this.$table = $(this.element).DataTable(dtOptions);
        },
        _enableTrigger() {
            /*
            $(this.element).children('a[data-render="modal"]').on(
                    'click',
                    this._loadRemoteModal().bind(this)
                    );*/
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