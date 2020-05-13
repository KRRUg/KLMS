const $ = require('jquery');
require('bootstrap');

$('#editModal').on('show.bs.modal', function (event) {
    let button = $(event.relatedTarget);
    let modal = $(this);

    modal.find('form').trigger('reset');
    if (button.attr('id') === "new") {
        modal.find('#user').prop('readonly', false);
    } else {
        let id = button.data('id');
        let name = button.data('name');
        let perm = button.data('perm');

        modal.find('#user').val(name).prop('readonly', true);
        for (let k in perm) {
            modal.find('#perm_' + k).prop('checked', true);
        }
    }
});