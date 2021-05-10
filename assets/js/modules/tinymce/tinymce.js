import tinymce from 'tinymce/tinymce';

import 'tinymce/icons/default';
import 'tinymce/themes/silver';

import 'tinymce/plugins/paste';
import 'tinymce/plugins/link';
import 'tinymce/plugins/image';
import 'tinymce/plugins/code';
import 'tinymce/plugins/searchreplace';
import 'tinymce/plugins/lists';
import 'tinymce/plugins/hr';

export function init() {
    tinymce.init({
        selector: 'textarea.wysiwyg',
        theme: 'silver',
        height : '640',
        plugins: 'image paste link code searchreplace lists hr',
        toolbar: 'undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | numlist bullist | outdent indent',

        // image plugin
        relative_urls: false,
        image_list: '/admin/media.json?filter=image',

        // link plugin
        link_default_protocol: 'https',
        link_list: '/admin/media.json?filter=doc',
    });
}
