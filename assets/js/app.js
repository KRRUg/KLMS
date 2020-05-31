// Shared JS File

//require('../css/app.css');

console.log('Hello Webpack Encore! Edit me in assets/js/app.js');


//Import Bootstrap4
require('jquery');
require('bootstrap');
require('../css/_scss/bootstrap-krru.scss');

require('@fortawesome/fontawesome-free/css/all.min.css');

require('select2');
require('select2/dist/css/select2.css');
require('select2-bootstrap4-theme/dist/select2-bootstrap4.min.css');
require('select2/dist/js/i18n/de.js');
//Images

let select2 = require('./modules/select2.js');

$(document).ready(function () {
    console.log("App module loaded!");
    select2.init();
});

