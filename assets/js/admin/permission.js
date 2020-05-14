const $ = require('jquery');
require('jquery-serializejson');
require('bootstrap');

let EditModal = function($wrapper) {
    this.$modal = $wrapper;
    this.$form = $wrapper.find('form');

    this.$modal.on(
        'show.bs.modal',
        this.handleModalShow.bind(this)
    );

    this.$modal.on(
        'hide.bs.modal',
        this.handleModalHide.bind(this)
    );

    this.$modal.on(
        'submit',
        'form',
        this.handleFormSubmit.bind(this)
    );
};

$.extend(EditModal.prototype, {
    handleModalShow(e) {
        let button = $(e.relatedTarget);
        this.clearErrors();
        this.$form.trigger('reset');
        if (button.attr('id') === "new") {
            this.$form.find('#user').prop('readonly', false);
        } else {
            let id = button.data('id');
            let name = button.data('name');
            let perm = button.data('perm');

            this.$form.find('#user').val(name).prop('readonly', true);
            for (let k in perm) {
                this.$form.find('#perm_' + k).prop('checked', true);
            }
        }
    },

    handleModalHide(e) {

    },

    handleFormSubmit(e) {
        e.preventDefault();
        let $form = $(e.currentTarget);
        this._saveData($form.serializeJSON());
    },

    _saveData(data) {
        return new Promise((resolve, reject) => {
            $.ajax({
                method: 'POST',
                data: JSON.stringify(data)
            }).then((data, textStatus, jqXHR) => {
                this.$modal.modal('hide');
                console.log("done");
            }).catch((jqXHR) => {
                let errorData = JSON.parse(jqXHR.responseText);
                this._mapErrorsToForm(errorData.errors);
            });
        });
    },

    clearErrors() {
        this.$form.find('.js-field-error').remove();
        this.$form.find('.form-group').removeClass('has-error');
    },

    _mapErrorsToForm(errorData) {
        this.clearErrors();
        this.$form.find(':input').each((key, value) => {
            let $field = $(value);
            let fieldName = $field.attr('name');
            if (errorData[fieldName]) {
                let $wrapper = $field.closest('.form-group');
                let $error = $('<span class="js-field-error form-error-message"></span>');
                $error.html(errorData[fieldName]);
                $wrapper.append($error);
                $wrapper.addClass('has-error');
            }
        });
        console.log(errorData);
    },
});

$(document).ready(() => {
    new EditModal($('#editModal'));
});
