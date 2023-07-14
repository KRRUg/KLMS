import { Controller } from "@hotwired/stimulus";
import $ from 'jquery';

export default class extends Controller {
    // clears the forms on a modal when it is closed
    connect() {
        $(this.element).on('hidden.bs.modal', function () {
            $(this).find('form').trigger('reset');
        })
    }
}