import $ from 'jquery';
import 'jquery-serializejson';
import dTLang from '../modules/dataTables/dataTables.js';

let UserTable = function ($wrapper) {
    this.remoteTarget = $wrapper.attr('data-remote-target');

    this.$table = $wrapper.DataTable({
        searchHighlight: true,
        language : dTLang,
        ajax: {
            url: this.remoteTarget,
            dataSrc: 'items',
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
        $anchor.attr('data-uuid', user.uuid);
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
        for (let i in permissions) {
            renderString += '<span>' + permissions[i] + '</span><br/>';
        }
        return renderString;
    },
});

let NewModal = function($wrapper, onupdate) {
    this.$modal = $wrapper;
    this.$form = $wrapper.find('form');
    this.onupdate = onupdate;
    this.remoteTarget = $wrapper.data('remote-target');

    this.$modal.on(
        'show.bs.modal',
        this.handleModalShow.bind(this)
    );

    this.$modal.on(
        'submit',
        'form',
        this.handleFormSubmit.bind(this)
    );
};

$.extend(NewModal.prototype, {
    handleModalShow(e) {
        this.$form.trigger('reset');

    },

    handleFormSubmit(e) {
        e.preventDefault();
        let $form = $(e.currentTarget);

        this._saveData($form.serializeJSON())
            .then(() => {
                this.onupdate();
                this.$modal.modal('hide');
            }).catch((errorData) => {
                console.log(errorData);
            });
    },

    _saveData(data) {
        return new Promise((resolve, reject) => {
            $.ajax({
                url: this.remoteTarget,
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
});

let EditModal = function ($wrapper, onupdate) {
    this.$modal = $wrapper;
    this.$form = $wrapper.find('form');
    this.onupdate = onupdate;
    this.remoteTarget = $wrapper.data('remote-target');

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
        let $button = $(e.relatedTarget);
        this.uuid = $button.data('uuid');

        this.$modal.addClass('invisible');

        this.clearErrors();
        this.$form.trigger('reset');

        this._getData()
            .then((data) => {
                this._mapJsonToForm(data);
                this.$modal.removeClass('invisible');
            }).catch(() => {
                console.error('ajax requst problem');
            });
    },

    handleModalHide(e) {
        this.uuid = undefined;
    },

    _mapJsonToForm(data) {
        let formName = this.$form.attr('name');
        this.$modal.find('.userName').text(data.user.nickname);
        this.$form.find('input[name="' + formName + '[perm][]"]')
            .each((i, k) => {
                let $k = $(k);
                $k.prop('checked', data.perm.includes($k.val()));
            });
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

    _getData() {
        return new Promise((resolve, reject) => {
            $.ajax({
                method: 'GET',
                url: this.remoteTarget + '/' + this.uuid,
                dataType: 'json',
            }).then((data, textStatus, jqXHR) => {
                resolve(data);
            }).catch((jqXHR) => {
                reject();
            });
        });
    },

    _saveData(data) {
        return new Promise((resolve, reject) => {
            $.ajax({
                url: this.remoteTarget + '/' + this.uuid,
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
    },
});

$(document).ready(() => {
    const ut = new UserTable($('#userTable'));
    const em = new EditModal($('#editModal'), ut.reloadDataTable.bind(ut));
    const nm = new NewModal($('#newModal'), ut.reloadDataTable.bind(ut));
});
