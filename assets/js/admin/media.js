import $ from 'jquery';
import ConfirmModal from '../components/confirmModal.js';

const MediaPreview = function($root, $modal) {
    this.$root = $root;
    this.modal = new ConfirmModal($modal);
    const boxes = $root.children('[data-id]');

    boxes.each((_,elem) => {
        const $elem = $(elem);
        const $butDel = $elem.find('.button-del');
        const href = $butDel.attr('href');
        $butDel.attr('href', '#');
        $butDel.on('click', _ => this.modal.show(href));
    });
}

$(document).ready(() => {
    new MediaPreview($('#mediaList'), $('#confirmModal'));
});