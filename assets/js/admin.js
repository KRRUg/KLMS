/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
var tinymce = require('tinymce/tinymce');

// A theme is also required
//require.context(
//  'file-loader?name=[path][name].[ext]&context=node_modules/tinymce!tinymce/skins',
//  true,
//  /.*/
//);

require('tinymce/themes/silver');

require('tinymce/plugins/paste');
require('tinymce/plugins/link');

function test() {
    console.log("Admin module loaded!");
}

$( document ).ready(function() {
    test();
    initTinyMCE();
});


function initTinyMCE() {
    tinymce.init({
        selector: 'textarea.wysiwyg',
        theme: 'silver'
    });
}