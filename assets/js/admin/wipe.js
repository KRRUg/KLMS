import $ from 'jquery';

const WipeAdmin = function (form) {
    this.form = form;
    this.boxes = [...this.form.querySelectorAll("input[type=checkbox]")];

    this.boxes.forEach((b) => b.addEventListener(
        'change',
        this._processChangeAction.bind(this)
    ));
};

$.extend(WipeAdmin.prototype, {
    _findChildren(base) {
        const dependency = base.dataset.dependency.split(',');
        return dependency.map((id) => this.form.querySelector("#form_"+id));
    },
    _findParent(base) {
        const id = base.id.replace("form_", "");
        return this.boxes.filter((bx) => bx.dataset.dependency.split(',').includes(id));
    },
    _setChildrenOn($elem) {
        for (const child of this._findChildren($elem)) {
            child.checked = true;
        }
    },
    _setParentOff($elem) {
        for (const parent of this._findParent($elem)) {
            parent.checked = false;
        }
    },
    _processChangeAction(e) {
        let $actionBox = e.currentTarget;
        let on = $actionBox.checked;

        //(on ? this._setChildrenOn : this._setParentOff)($actionBox);

        if (on) {
            this._setChildrenOn($actionBox);
        } else {
            this._setParentOff($actionBox)
        }
    },
});

$(document).ready(() => {
    let wipeAdmin = new WipeAdmin(document.querySelector("form"));
});