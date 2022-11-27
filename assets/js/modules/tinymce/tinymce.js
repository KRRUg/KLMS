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
import 'tinymce/plugins/searchreplace';
import 'tinymce/plugins/hr';

export function init() {
    tinymce.init({
        selector: 'textarea.wysiwyg',
        theme: 'silver',
        height : '640',
        //plugins: 'image paste link table code lists advlist',
        plugins: [
            'advlist lists link image anchor',
            'code fullscreen',
            'media table importcss searchreplace hr'
        ],
        toolbar: 'undo redo | formatselect | ' +
            'bold italic backcolor | alignleft aligncenter ' +
            'alignright alignjustify | bullist numlist | outdent indent | ' +
            'removeformat',
        font_formats: '',
        fontsize_formats:'0.5rem 0.75rem 1rem 1.25rem 1.5rem 1.75rem 2rem',
        relative_urls: false,
        remove_script_host: false,

        // image plugin
        image_list: '/admin/media/list.json?filter=image',

        // link plugin
        link_default_protocol: 'https',
        link_list: '/admin/media/list.json',
        default_link_target: '_blank',

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