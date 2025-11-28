import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static targets = ["container", "add", "item", "empty"];

    static values = {
        index: Number,
        prototype: String,
        max: Number,
    };

    connect() {
        if (!this.hasIndexValue || Number.isNaN(this.indexValue)) {
            this.indexValue = this.itemTargets.length;
        }
        this.toggleEmptyState();
        this.updateAddState();
    }

    add(event) {
        event.preventDefault();
        if (this.reachedLimit()) {
            return;
        }

        const html = this.prototypeValue.replace(/__name__/g, this.indexValue);
        this.indexValue += 1;
        const element = this.createElement(html);
        if (!element) {
            return;
        }

        this.containerTarget.appendChild(element);
        this.toggleEmptyState();
        this.updateAddState();
    }

    remove(event) {
        event.preventDefault();
        const removeButton = event.currentTarget;
        const item = removeButton.closest('[data-form-collection-target="item"]');
        if (!item) {
            return;
        }

        item.remove();
        this.toggleEmptyState();
        this.updateAddState();
    }

    reachedLimit() {
        if (!this.hasMaxValue || this.maxValue <= 0) {
            return false;
        }
        return this.itemTargets.length >= this.maxValue;
    }

    toggleEmptyState() {
        if (!this.hasEmptyTarget) {
            return;
        }
        this.emptyTarget.classList.toggle('d-none', this.itemTargets.length > 0);
    }

    updateAddState() {
        if (!this.hasAddTarget) {
            return;
        }
        const disabled = this.reachedLimit();
        this.addTargets.forEach((button) => {
            button.disabled = disabled;
        });
    }

    createElement(html) {
        const template = document.createElement('template');
        template.innerHTML = html.trim();
        return template.content.firstElementChild;
    }
}
