import $ from 'jquery';
import dTLang from '../../modules/dataTables/dataTables.js';
import './ajaxModal.js';
import '../../modules/confirmModal/confirmModal.js';
;
(function ($, window, document, undefined) {

    "use strict";

    let AdminDataTable = function (element, options) {
        this.element = element;

        let defaults = {
            remoteTarget: element.dataset.remoteTarget,
            serverSideProcessing: element.dataset.serverSideProcessing !== "false",
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
                language: dTLang,
                pageLength: 25,
            };

            if (this.settings.remoteTarget) {
                if (this.settings.serverSideProcessing) {
                    dtOptions.ajax = {
                        url: this.settings.remoteTarget,
                        dataSrc: 'items',
                        dataFilter: function (data) {
                            //Process server response from KLMS API
                            var json = jQuery.parseJSON(data);
                            json.recordsTotal = json.total;
                            json.recordsFiltered = json.total;
                            json.data = json.items;

                            return JSON.stringify(json);
                        },
                        data: function (dtData) {
                            //Add data for KLMS API for Ajax Request
                            let request = {};
                            request.draw = dtData.draw;
                            request.q = dtData.search.value;
                            request.limit = dtData.length;
                            request.page = Math.floor(dtData.start / dtData.length) + 1;
                            request.sort = {};

                            for (let sortCol of dtData.order) {
                                let colName = dtData.columns[sortCol.column].data;
                                request.sort[colName] = sortCol.dir;
                            }

                            return request;
                        }
                    };
                    dtOptions.serverSide = true;
                    dtOptions.processing = true;
                } else {
                    dtOptions.ajax = {
                        url: this.settings.remoteTarget,
                        dataSrc: 'items',
                    };
                }
            }

            let columnDefs = [];
            $(this.element).children("thead").find("th").each((colIndex, colElement) => {
                let colDef = {};

                if (colElement.dataset.renderFunction) {
                    let renderFunction = JSON.parse(colElement.dataset.renderFunction);

                    colDef.targets = colIndex;
                    colDef.render = function (data, type, row, meta) {
                        let elem = document.createElement(renderFunction.elemType);

                        Object.keys(renderFunction.attributes).forEach(attrName => {
                            let attrValue = renderFunction.attributes[attrName];

                            if (typeof attrValue === 'object' && attrValue !== null) {
                                let h = (typeof attrValue.prepend !== 'undefined') ? attrValue.prepend : "";

                                if (typeof attrValue.data !== 'undefined') {
                                    if(attrValue.data.includes('.')) {
                                        const object = attrValue.data.split('.')[0];
                                        const parameter = attrValue.data.split('.')[1];

                                        let s = document.createElement("SPAN");
                                        s.textContent = row[object][parameter];
                                        h += s.innerHTML;
                                    } else {
                                        let s = document.createElement("SPAN");
                                        s.textContent = row[attrValue.data];
                                        h += s.innerHTML;
                                    }
                                }

                                h += (typeof attrValue.append !== 'undefined') ? attrValue.append : "";

                                const matches = h.matchAll(/--(\w+)--|--(\w+.\w+)--/g);
                                for (const match of matches) {
                                    if(match[2] !== undefined) {
                                        const object = match[2].split('.')[0];
                                        const parameter = match[2].split('.')[1];
                                        //If match contains an Object
                                        if (row[object][parameter]) {
                                            let s = document.createElement("SPAN");
                                            s.textContent = row[object][attrValue.data];
                                            let rep = row[object][parameter];
                                            h = h.replace(match[0], rep);
                                        }
                                    } else {
                                        //if match contains a string
                                        if (row[match[1]]) {
                                            let s = document.createElement("SPAN");
                                            s.textContent = row[attrValue.data];
                                            let rep = row[match[1]];
                                            h = h.replace(match[0], rep);
                                        }
                                    }
                                }

                                attrValue = h;
                            }

                            elem.setAttribute(attrName, attrValue);
                        });

                        if ((typeof data === 'undefined' || data === null) && colElement.dataset.defaultContent) {
                            data = colElement.dataset.defaultContent;
                        }

                        elem.innerHTML = data;
                        return elem.outerHTML;
                    };
                } else if (this.settings.remoteTarget) {
                    colDef.targets = colIndex;
                    colDef.render = $.fn.dataTable.render.text();
                }

                
                if (colDef) {
                    columnDefs.push(colDef);
                }
            });

            if (columnDefs) {
                dtOptions.columnDefs = columnDefs;
            }

            this.$table = $(this.element).DataTable(dtOptions);
        }
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