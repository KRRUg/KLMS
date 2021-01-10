import $ from "jquery";

import flatpickr from "flatpickr";
import { German } from "flatpickr/dist/l10n/de.js"
import "flatpickr/dist/flatpickr.min.css";

const DateTimePicker = function (options) {
    this.wrap("<div class=\"input-group w-100\"></div>");
    this.after("<div class=\"input-group-append\">" +
            "<a class=\"input-group-text btn\" data-clear><i class=\"fa fa-times\"></i></a>" +
            "<a class=\"input-group-text btn\" data-toggle><i class=\"fa fa-calendar-alt\"></i></a>" +
            "</div>");
    this.css('background-color', 'unset');
    this.attr('data-input', true);

    this.parent().flatpickr({
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
    });

    this.each(function (index) {
        if ($(this).data('time-start')) {
            var calAttr = "maxDate";
            var targetPicker = $(this).data('time-start');
        } else if ($(this).data('time-end')) {
            var calAttr = "minDate";
            var targetPicker = $(this).data('time-end');
        }

        $(this).change(function (e) {
            let currentFp = e.target.parentElement._flatpickr;
            let targetFp = document.querySelector(targetPicker).parentElement._flatpickr;

            let dateInValue = currentFp.selectedDates; // get current date from first date picker
            if(dateInValue.length > 0) {            
                var currentDateObj = new Date(dateInValue);
            } else {
                var currentDateObj = "";
            }
            targetFp.set(calAttr, currentDateObj);
        });
    });



    return this;
};

$.fn.datetime = DateTimePicker;