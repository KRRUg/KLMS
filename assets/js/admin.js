import $ from "jquery";

let tinymce = require('./modules/tinymce/tinymce.js');
import './modules/adminDataTable/jquery.adminDataTable.js';


$( document ).ready(function() {
    tinymce.init();
    $('.admin-data-table').AdminDataTable();
    $('.select2-enable').Select2();
});
