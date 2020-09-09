import $ from 'jquery';
import ConfirmModal from '../components/confirmModal.js';

const MediaPreview = function($root, confirmModal, $filter, $search) {
    this.$root = $root;
    this.$filter = $filter;
    this.$search = $search;
    this.modal = new ConfirmModal(confirmModal);
    this.boxes = $root.children('[data-id]');

    this.boxes.each((_,elem) => {
        const $elem = $(elem);
        const $butDel = $elem.find('.button-del');
        const href = $butDel.attr('href');
        $butDel.attr('href', '#');
        $butDel.on('click', _ => this.modal.show(href));
    });

    this.showOnly(this.$filter.val(), this.$search.val());
    this.$filter.on('change', _ => {
        this.showOnly(this.$filter.val(), this.$search.val());
    });
    this.$search.on('input', v => {
        this.showOnly(this.$filter.val(), this.$search.val());
    });
}

$.extend(MediaPreview.prototype,{
    showOnly(mime, search) {
        const ml = mime.toLowerCase();
        const sl = search.toLowerCase();
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
    },
});

$(document).ready(() => {
    new MediaPreview($('#mediaList'), $('#confirmModal'), $('#filterSelect'), $('#searchInput'));
});