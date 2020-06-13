// Shared JS File

require('./modules/sentry');
//Import Bootstrap4
require('jquery');
require('bootstrap');
require('../css/_scss/bootstrap-krru.scss');

require('@fortawesome/fontawesome-free/css/all.min.css');

let select2 = require('./modules/select2.js');

$(document).ready(function () {
    console.log("App module loaded!");
    select2.init();
});

