
let tinymce = require('./modules/tinymce');

$( document ).ready(function() {
    console.log("Admin module loaded!");
    tinymce.init();
});
