import { Controller } from "@hotwired/stimulus";

import tinymce from 'tinymce/tinymce';

import 'tinymce/models/dom';
import 'tinymce/icons/default';
import 'tinymce/themes/silver';

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
import 'tinymce/plugins/searchreplace';

export default class extends Controller {
    static values = {
        height: Number,
    }

    initialize() {
        this.defaults = {
            license_key: 'gpl',
            theme: 'silver',
            //plugins: 'image paste link table code lists advlist',
            plugins: [
                'lists', 'link', 'image', 'anchor',
                'code', 'fullscreen',
                'media', 'table', 'searchreplace'
            ],
            toolbar: 'undo redo | fontsize styles | ' +
                'bold italic backcolor removeformat | alignleft aligncenter ' +
                'alignright alignjustify | bullist numlist | outdent indent | ' +
                'code fullscreen',
            removed_menuitems: 'fontfamily',
            font_family_formats: '',
            font_size_formats:'0.5rem 0.75rem 1rem 1.25rem 1.5rem 1.75rem 2rem',
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
            table_default_styles: {
                width: '100%'
            },
            table_class_list: [
                {title: 'None', value: ''},
                {title: 'Table', value: 'table'},
                {title: 'Striped', value: 'table table-striped table-hover'},
                {title: 'Bordered', value: 'table table-bordered table-hover'},
                {title: 'Small Table', value: 'table table-sm'},
            ],
            style_formats: {

            }
        };
    }

    connect() {
        const height = this.heightValue || 640;
        const config = Object.assign({ target: this.element, height: height }, this.defaults)
        tinymce.init(config);
    }

    disconnect() {
        tinymce.get(this.element.id).remove();
    }
}