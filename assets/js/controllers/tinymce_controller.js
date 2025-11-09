import { Controller } from "@hotwired/stimulus";

import tinymce from 'tinymce/tinymce';

import 'tinymce/icons/default';
import 'tinymce/themes/silver';
import 'tinymce/models/dom';

import 'tinymce/plugins/advlist';
import 'tinymce/plugins/anchor';
import 'tinymce/plugins/code';
import 'tinymce/plugins/fullscreen';
import 'tinymce/plugins/image';
import 'tinymce/plugins/importcss';
import 'tinymce/plugins/link';
import 'tinymce/plugins/lists';
import 'tinymce/plugins/media';
import 'tinymce/plugins/searchreplace';
import 'tinymce/plugins/table';

const DEFAULT_PLUGINS = [
    'advlist',
    'anchor',
    'code',
    'fullscreen',
    'image',
    'importcss',
    'link',
    'lists',
    'media',
    'searchreplace',
    'table',
];

export default class extends Controller {
    static values = {
        height: Number,
        licenseKey: String,
    }

    initialize() {
        this.defaults = {
            theme: 'silver',
            plugins: DEFAULT_PLUGINS,
            toolbar: 'undo redo | formatselect | ' +
                'bold italic backcolor | alignleft aligncenter ' +
                'alignright alignjustify | bullist numlist | outdent indent | ' +
                'link image media table | code fullscreen | removeformat',
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
            ],
        };
    }

    connect() {
        const height = this.heightValue || 640;
        const licenseKey = this.hasLicenseKeyValue ? this.licenseKeyValue : 'gpl';
        const config = Object.assign(
            { target: this.element, height: height, license_key: licenseKey },
            this.defaults,
        );
        tinymce.init(config);
    }

    disconnect() {
        const instance = tinymce.get(this.element.id);
        if (instance) {
            instance.remove();
        }
    }
}