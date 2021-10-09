import '../css/app.scss';

//Shared JS
import $ from 'jquery';
import 'bootstrap';

import 'mark.js';
import 'mark.js/dist/jquery.mark.js';

import './modules/sentry/sentry.js';
import './modules/adminDataTable/jquery.adminDataTable.js';
//import './modules/dateTimePicker/datetimepicker.js';
import './modules/dateTimePicker/jquery.dateTimePicker.js';
import { German } from "flatpickr/dist/l10n/de.js"
import 'lightbox2';

import select2Init from './modules/select2/select2';

const cfi = require('bs-custom-file-input');

import './modules/confirmModal/confirmModal.js';

$(document).ready(function () {
    cfi.init();
    $('.datetimepicker').DateTimePicker();
    $('.datepicker').flatpickr({
        altInput: true,
        altFormat: "d.m.Y",
        dateFormat: "Y-m-d",
        locale: German
    }
    );
    $('.datatable').AdminDataTable();
    $('.select2-enable').Select2();

    setTimeout(function () {
        $('.alert-flash-msg').alert('close');
    }, 6500);

    $('.modal').on('shown.bs.modal', function(e) {
        $(this).find('[autofocus]').focus();
    });

});
