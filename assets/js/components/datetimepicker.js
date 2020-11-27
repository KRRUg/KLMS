import $ from "jquery";

import flatpickr from "flatpickr";
import "flatpickr/dist/flatpickr.min.css";

const DateTimePicker = function(options) {
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
        dateFormat: 'Y-m-d H:i',
        monthSelectorType: "static",
        minuteIncrement: 10,
        locale: {
            "firstDayOfWeek": 1 // start week on Monday
        },
    });
    return this;
};

$.fn.datetime = DateTimePicker;