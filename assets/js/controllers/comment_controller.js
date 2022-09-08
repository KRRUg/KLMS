import { Controller } from "@hotwired/stimulus";
// import $ from 'jquery'

export default class extends Controller {
    static targets = ['hideable', 'form'];

    toggleForm() {
        this.hideableTargets.forEach(el => {
            el.hidden = !el.hidden;
        });
    }

    cancel() {
        this.formTargets.forEach(el =>{
            el.reset()
        });
        this.toggleForm();
    }
}
