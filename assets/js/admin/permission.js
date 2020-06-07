const $ = require('jquery');

const dT = require('../modules/dataTables/dataTables.js');

let UserTable = function ($wrapper) {
    this.remoteTarget = $wrapper.attr('data-remote-target');

    this.$table = $wrapper.DataTable({
        searchHighlight: true,
        language : dT.dataTableLang,
        ajax: {
            url: this.remoteTarget,
            dataSrc: ""
        },
        columns: [
            {data: 0},
            {data: 1}
        ],
        columnDefs: [
            {
                "targets": 0,
                "render": this._renderUser.bind(this)
            },
            {
                "targets": 1,
                "render": this._renderPermissions.bind(this)
            }]
    });
};

$.extend(UserTable.prototype, {
    reloadDataTable() {
        this.$table.ajax.reload();
    },

    _renderUser(user) {
        let $render = $('<div>');

        let $anchor = $('<a href="#" data-toggle="modal" data-target="#editModal">');
        $anchor.attr('data-id', user.uuid);
        $anchor.text(user.nickname);

        let $mail = $('<small class="text-muted">');
        $mail.text(user.email);

        $render.append($anchor);
        $render.append('<br/>');
        $render.append($mail);

        return $render.html();
    },

    _renderPermissions(permissions) {
        let renderString = '';
        for (i in permissions) {
            renderString += '<span>' + permissions[i] + '</span><br/>';
        }
        return renderString;
    },
});


//TODO: Move to own js File + modal component
let EditModal = function ($wrapper, onupdate) {
    this.$modal = $wrapper;
    this.$form = $wrapper.find('form');
    this.onupdate = onupdate;

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
            this.$form.find('input[name="perm[]"]')
                    .each((i, k) => {
                        let $k = $(k);
                        $k.prop('checked', perm.includes($k.val()));
                    });
        }
    },

    handleModalHide(e) {
    },

    handleFormSubmit(e) {
        e.preventDefault();
        let $form = $(e.currentTarget);
        this._saveData($form.serializeJSON())
                .then(() => {
                    this.onupdate();
                    this.$modal.modal('hide');
                }).catch((errorData) => {
            this._mapErrorsToForm(errorData);
        });
    },

    _saveData(data) {
        return new Promise((resolve, reject) => {
            $.ajax({
                method: 'POST',
                contentType: 'application/json',
                dataType: 'json',
                data: JSON.stringify(data)
            }).then((data, textStatus, jqXHR) => {
                resolve();
            }).catch((jqXHR) => {
                let errorData = JSON.parse(jqXHR.responseText);
                reject(errorData.errors);
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
    const ut = new UserTable($('#userTable'));
    //const em = new EditModal($('#editModal'), ut.updateTable.bind(ut));
});
