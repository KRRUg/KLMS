const $ = require('jquery');
require('jquery-serializejson');
require('bootstrap');

let UserTable = function($wrapper) {
    this.$table = $wrapper;
    this.$body = $wrapper.find('tbody');
};

$.extend(UserTable.prototype, {
    updateTable() {
        this._loadData()
            .then((data) => this._fillTable(data))
            .catch((error) => { console.log(error); });
    },

    _fillTable(data) {
        this.$body.empty();
        // TODO replace me with DataTable
        for (let i in data) {
            let user = data[i][0];
            let perm = data[i][1];
            let $tr = $('<tr>');
            let $td1 = $('<td>');
            let $a = $('<a href="" data-toggle="modal" data-target="#editModal">' + user.nickname + '</a>');
            $a.data('name', user.nickname);
            $a.data('perm', perm);
            $td1.append($a);
            $td1.append($('<br/><small class="text-muted">' + user.email + '</small>'));
            let $td2 = $('<td>');
            for (j in perm) {
                $td2.append($('<span>'+ perm[j] + '</span><br/>'));
            }
            $tr.append($td1);
            $tr.append($td2);
            this.$body.append($tr);
        }
    },

    _loadData() {
        return new Promise((resolve, reject) => {
            $.ajax({
                method: 'GET',
                dataType: 'json'
            }).then((data) => {
                resolve(data);
            }).catch((jqXHR) => {
                let errorData = JSON.parse(jqXHR.responseText);
                reject(errorData.errors);
            });
        });
    }
});

let EditModal = function($wrapper, onupdate) {
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
                .each((i,k)=> {
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
    const em = new EditModal($('#editModal'), ut.updateTable.bind(ut));
    //ut.updateTable();
});
