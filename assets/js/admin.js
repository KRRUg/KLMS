let tinymce = require('./modules/tinymce/tinymce.js');

$( document ).ready(function() {
    console.log("Admin module loaded!");
    tinymce.init();
});
