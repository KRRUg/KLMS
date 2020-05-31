let tinymce = require('tinymce/tinymce');

require('tinymce/themes/silver');
require('tinymce/plugins/paste');
require('tinymce/plugins/link');

export function init() {
    tinymce.init({
        selector: 'textarea.wysiwyg',
        theme: 'silver'
    });
}
