let tinymce = require('./modules/tinymce.js');
import 'mark.js';
import 'mark.js/dist/jquery.mark.js';
import './modules/dataTables/dataTables.js';

$( document ).ready(function() {
    console.log("Admin module loaded!");
    tinymce.init();
});
