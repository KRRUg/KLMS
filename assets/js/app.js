// Shared JS File

//require('../css/app.css');

console.log('Hello Webpack Encore! Edit me in assets/js/app.js');


//Import Bootstrap4
require('jquery');
require('bootstrap');
require('../css/_scss/bootstrap-krru.scss');

require('@fortawesome/fontawesome-free/css/all.min.css');

let cfi = require('bs-custom-file-input');

$( document ).ready(function() {
    cfi.init();
});

//Images
