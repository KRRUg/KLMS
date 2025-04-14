import {Controller} from '@hotwired/stimulus';
import $ from 'jquery';

/*
* The following line makes this controller "lazy": it won't be downloaded until needed
* See https://github.com/symfony/stimulus-bridge#lazy-controllers
*/
/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['form', 'status']

    async submitForm(event) {
        const $form = $(this.element).find('form');
        const $errors = $(this.element).find('.invalid-feedback');
        const $invalidFields = $(this.element).find('.is-invalid');
        event.preventDefault();

        const checkmark = '<a href="#" class="badge badge-pill ml-3 badge-primary" ' +
            'data-value="true" data-action="hideEmail" data-index="1" data-target="team-section-1" ' +
            'style="pointer-events: none; cursor: default;">' +
            'Erfolgreich! ' +
            '<i class="fas fa-check" style="color: LimeGreen;"></i></a>'

        try {
            await $.ajax({
                url: $form.prop('action'),
                method: $form.prop('method'),
                data: $form.serialize(),
            });
            this.statusTarget.innerHTML = checkmark;
            $errors.remove();
            $invalidFields.removeClass('is-invalid').addClass('is-valid');
        } catch (e) {
            this.statusTarget.innerHTML = 'Error!';
            this.element.innerHTML = e.responseText;
        }
    }
}