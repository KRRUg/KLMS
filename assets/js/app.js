// Shared JS File

//Import Bootstrap4
import 'jquery';
import 'bootstrap';
import '../css/_scss/bootstrap-krru.scss';
import '@fortawesome/fontawesome-free/css/all.min.css';

import 'mark.js';
import 'mark.js/dist/jquery.mark.js';
import './modules/dataTables/dataTables.js';

let select2 = require('./modules/select2/select2.js');

$(document).ready(function () {
    console.log("App module loaded!");
    select2.init();
});

