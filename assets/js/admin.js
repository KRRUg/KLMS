let tinymce = require('./modules/tinymce/tinymce.js');
import './modules/adminDataTable/jquery.adminDataTable.js';


$( document ).ready(function() {
    console.log("Admin module loaded!");
    
    tinymce.init();
    $('.admin-data-table').AdminDataTable();
});
