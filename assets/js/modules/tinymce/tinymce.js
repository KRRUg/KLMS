import tinymce from 'tinymce/tinymce';

import 'tinymce/icons/default';
import 'tinymce/themes/silver';

import 'tinymce/plugins/paste';
import 'tinymce/plugins/link';
import 'tinymce/plugins/image';
import 'tinymce/plugins/table';
import 'tinymce/plugins/code';
import 'tinymce/plugins/lists';
import 'tinymce/plugins/fullscreen';
import 'tinymce/plugins/code';
import 'tinymce/plugins/anchor';
import 'tinymce/plugins/media';
import 'tinymce/plugins/advlist';
import 'tinymce/plugins/importcss';


export function init() {
    tinymce.init({
        selector: 'textarea.wysiwyg',
        theme: 'silver',
        height : '640',
        //plugins: 'image paste link table code lists advlist',
        plugins: [
            'advlist lists link image anchor',
            'code fullscreen',
            'media table importcss'
        ],
        toolbar: 'undo redo | formatselect | ' +
            'bold italic backcolor | alignleft aligncenter ' +
            'alignright alignjustify | bullist numlist outdent indent | ' +
            'removeformat',
        // image plugin
        relative_urls: false,
        image_list: '/admin/media.json?filter=image',

        // link plugin
        link_default_protocol: 'https',
        link_list: '/admin/media.json?filter=doc',

        //table plugin
        table_default_attributes: {
            class: 'table'
        },
        table_class_list: [
            {title: 'None', value: ''},
            {title: 'Table', value: 'table'},
            {title: 'Striped', value: 'table table-striped table-hover'},
            {title: 'Bordered', value: 'table table-bordered table-hover'},
        ]
    });
}