const $ = require('jquery');
require('bootstrap');

$('#editModal').on('show.bs.modal', function (event) {
    let button = $(event.relatedTarget);
    let modal = $(this);

    modal.find('form').trigger('reset');
    if (button.attr('id') === "new") {
        modal.find('#user-name').prop('readonly', false);
    } else {
        let id = button.data('id');
        let name = button.data('name');
        let perm = button.data('perm');

        modal.find('#user-name').val(name).prop('readonly', true);
        modal.find('#id').val(id);
        for (let k in perm) {
            modal.find('#perm-' + perm[k].toLowerCase()).prop('checked', true);
        }
    }
});