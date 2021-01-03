import $ from 'jquery';
import 'mark.js';
import 'mark.js/dist/jquery.mark.js';

const MediaPreview = function($root, $filter, $search) {
    this.$root = $root;
    this.$filter = $filter;
    this.$search = $search;
    this.boxes = $root.children('[data-id]');

    this.showOnly(this.$filter.val(), this.$search.val());
    this.$filter.on('change', _ => {
        this.showOnly(this.$filter.val(), this.$search.val());
    });
    this.$search.on('input', _ => {
        this.showOnly(this.$filter.val(), this.$search.val());
    });
}

$.extend(MediaPreview.prototype,{
    showOnly(mime, search) {
        const ml = mime.toLowerCase();
        const sl = search.toLowerCase();
        this.$root.unmark();
        this.boxes.each((_, elem) => {
            const $elem = $(elem);
            const m = $elem.data('mime-type').toLowerCase();
            const n = $elem.data('name').toLowerCase();
            let show = true;
            show &= (mime === undefined || mime === 'all' || m.startsWith(ml));
            show &= (search === undefined || search === '' || n.includes(sl));
            if (show)
                $elem.show();
            else
                $elem.hide();
        });
        this.$root.mark(sl, { "exclude": ['.no-highlight'] });
    },
    getFileNames() {
        return this.boxes.map(function() {
            return $(this).data('name');
        }).get();
    },
});

const UploadDialog = function($modal, filenames) {
    this.$modal = $modal;
    let $form = $modal.find('form');
    let $input = $form.find('input[type=file]');
    let $checkbox = $form.find('input[type="checkbox"]');
    let $label = $form.find('label[for="' + $checkbox.attr('id') + '"]');

    let $alert = $("<div class='alert alert-warning'>Datei ist vorhanden und wird überschrieben!</div>");
    $label.after($alert);
    $checkbox.hide();
    $label.hide();
    $alert.hide();

    $modal.on('hidden.bs.modal', _ => {
        $form.trigger('reset');
        $alert.hide();
    });

    $input.on('change', _ => {
        const filename = $input.val().split('\\').pop();
        if (filenames.includes(filename)) {
            $checkbox.prop('checked', true);
            $alert.show();
        } else {
            $checkbox.prop('checked', false);
            $alert.hide();
        }
    });
}

$(document).ready(() => {
    const mp = new MediaPreview($('#mediaList'), $('#filterSelect'), $('#searchInput'));
    const ud = new UploadDialog($('#uploadModal'), mp.getFileNames());
});