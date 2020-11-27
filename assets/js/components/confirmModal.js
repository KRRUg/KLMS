// JS code for the confirm Modal

import $ from 'jquery';

const ConfirmModal = function($modal) {
    this.$modal = $modal;
    this.$confirm = $modal.find('.js-confirm');
}

$.extend(ConfirmModal.prototype, {
    show(href) {
        this.$confirm.attr('href', href);
        this.$modal.modal('show');
    }
});

export default ConfirmModal;