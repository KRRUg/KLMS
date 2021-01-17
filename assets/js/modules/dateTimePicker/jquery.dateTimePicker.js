import $ from "jquery";
import flatpickr from "flatpickr";
import { German } from "flatpickr/dist/l10n/de.js"
import "flatpickr/dist/flatpickr.min.css";

;
(function ($, window, document, undefined) {

    "use strict";

    let DateTimePicker = function (element, options) {
        this.element = element;

        let defaults = {
            maxDateTarget: element.dataset.timeStart,
            minDateTarget: element.dataset.timeEnd
        };

        this.settings = $.extend({}, defaults, options);
        this._defaults = defaults;
        this._name = "DateTimePicker";
        this.init();
    };

    // Avoid Plugin.prototype conflicts
    $.extend(DateTimePicker.prototype, {
        init() {
            this._initHTMLWrap();
            let dpOptions = {
                wrap: true,
                enableTime: true,
                enableSeconds: false,
                time_24hr: true,
                altInput: true,
                altFormat: "D j.m.Y, H:i",
                dateFormat: 'Y-m-d H:i',
                monthSelectorType: "static",
                minuteIncrement: 10,
                locale: German,
                onChange: []
            };

            if (this.settings.minDateTarget) {
                dpOptions.onChange.push((selectedDates, dateStr) => {
                    let targetFp = document.querySelector(this.settings.minDateTarget).parentElement._flatpickr;
                    targetFp.set("minDate", dateStr);
                });
            }

            if (this.settings.maxDateTarget) {
                dpOptions.onChange.push((selectedDates, dateStr) => {
                    let targetFp = document.querySelector(this.settings.maxDateTarget).parentElement._flatpickr;
                    targetFp.set("maxDate", dateStr);
                });
            }

            this.$picker = $(this.element).parent().flatpickr(dpOptions);
        },
        _initHTMLWrap() {
            $(this.element).wrap("<div class=\"input-group w-100\"></div>");
            $(this.element).after("<div class=\"input-group-append\">" +
                    "<a class=\"input-group-text btn\" data-clear><i class=\"fa fa-times\"></i></a>" +
                    "<a class=\"input-group-text btn\" data-toggle><i class=\"fa fa-calendar-alt\"></i></a>" +
                    "</div>");
            $(this.element).css('background-color', 'unset');
            $(this.element).attr('data-input', true);
        }
    });

    // A really lightweight plugin wrapper around the constructor,
    // preventing against multiple instantiations
    $.fn.DateTimePicker = function (options) {
        return this.each(function () {
            if (!$.data(this, "plugin_DateTimePicker")) {
                $.data(this, "plugin_DateTimePicker", new DateTimePicker(this, options));
            }
        });
    };

})(jQuery, window, document);