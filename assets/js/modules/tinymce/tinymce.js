import tinymce from 'tinymce/tinymce';

import 'tinymce/icons/default';
import 'tinymce/themes/silver';

import 'tinymce/plugins/paste';
import 'tinymce/plugins/link';

export function init() {
    tinymce.init({
        selector: 'textarea.wysiwyg',
        theme: 'silver'
    });
}
