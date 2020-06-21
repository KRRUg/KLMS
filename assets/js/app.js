import '../css/app.scss';

//Shared JS
import './modules/sentry.js';
import $ from 'jquery';
import 'bootstrap';

import 'mark.js';
import 'mark.js/dist/jquery.mark.js';
import './modules/dataTables/dataTables.js';

import select2Init from './modules/select2/select2';

$(document).ready(function () {
    console.log("App module loaded!");
    select2Init();
});

