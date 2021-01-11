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

import select2Init from './modules/select2/select2';

const cfi = require('bs-custom-file-input');

$( document ).ready(function() {
    cfi.init();
    select2Init();
    $('.datetimepicker').DateTimePicker();
    $('.datatable').AdminDataTable();
});
