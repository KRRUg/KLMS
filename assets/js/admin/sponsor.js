const $ = require('jquery');

let SponsorCategoryList = function ($wrapper) {
    this.$root = $wrapper;
    this.dataSource = $wrapper.attr('data-source-input');
    this.dispatcher = $({});

    this.init();
    this.draw();
    this.$root.on(
        'click',
        '.nav-item-action:not(.disabled)',
        this._processNavigationAction.bind(this)
    );
    this.$root.on(
        'click',
        '.nav-item',
        this._editItem.bind(this)
    );
    this.$root.on(
        'click',
        '.edit-item-form button',
        this._processInputFormAction.bind(this)
    );
};

$.extend(SponsorCategoryList.prototype, {
    init() {
        const srcJSON = $(this.dataSource).val();
        this.categoryList = JSON.parse(srcJSON);
    },
    draw() {
        this.$root.empty();
        this._buildHTML(this.$root[ 0 ], this.categoryList);
    },
    addNew() {
        this.categoryList.push({
            'name': 'Neue Kategorie',
            'can_delete': true,
        });
        this._synchroniseData()
        this.draw();
    },
    _buildHTML(baseElement, items) {
        let hasPrevItem = false;
        let hasNextItem = true;

        for (const [i, item] of items.entries()) {
            if (i === items.length - 1) {
                hasNextItem = false;
            }
            let ele = this._buildHTMLItem(item, i, hasPrevItem, hasNextItem);
            baseElement.appendChild(ele);
            hasPrevItem = true;
        }
    },
    _buildHTMLItem(item, index, hasPrevItem = false, hasNextItem = false) {
        let li = document.createElement("LI");
        li.setAttribute("class", "list-group-item d-flex");
        li.setAttribute("data-index", index);

        li.appendChild(this._buildActionElement("fas fa-arrow-up", "up", hasPrevItem, "Up"));
        li.appendChild(this._buildActionElement("fas fa-arrow-down", "down", hasNextItem, "Down"));

        let label = document.createElement("SPAN");
        label.setAttribute("class", "w-100 nav-item-label");
        let span = document.createElement('SPAN');
        span.setAttribute('class', 'nav-item pl-2');
        span.setAttribute("data-value", item.name);
        span.setAttribute("data-can-delete", item.can_delete);
        span.textContent = item.name;

        let i = document.createElement("I");
        i.setAttribute("class", "fas fa-edit pl-2 edit-img text-dark");
        span.appendChild(i);
        label.appendChild(span);
        li.appendChild(label);
        return li;
    },
    _buildActionElement(itemImgClass, action, enableItem = false, title = "") {
        let i = document.createElement("I");
        i.setAttribute("class", itemImgClass + " fa-fw");

        let actionItem = null;
        if (enableItem) {
            actionItem = document.createElement("A");
            actionItem.setAttribute("href", "#");
            actionItem.setAttribute("class", "nav-item-action");
            actionItem.setAttribute("title", title);
            actionItem.setAttribute("data-action", action);
        } else {
            actionItem = document.createElement("SPAN");
            actionItem.setAttribute("class", "nav-item-action text-disabled disabled");
        }
        actionItem.appendChild(i);

        return actionItem;
    },
    _processNavigationAction(e) {
        e.preventDefault();
        let $actionButton = $(e.currentTarget);
        let action = $actionButton.data("action");
        let index = Number($actionButton.parent().data("index"));
        switch (action) {
            case "up":
                [this.categoryList[index-1], this.categoryList[index]] = [this.categoryList[index], this.categoryList[index-1]];
                break;
            case "down":
                [this.categoryList[index+1], this.categoryList[index]] = [this.categoryList[index], this.categoryList[index+1]];
                break;
        }
        this._synchroniseData();
        this.draw();
    },
    _synchroniseData() {
        this.dispatcher.trigger("changed");
        const json = JSON.stringify(this.categoryList);
        $(this.dataSource).val(json);
    },
    _editItem(e) {
        e.preventDefault();

        $("form.edit-item-form").each((_, element) => {
            this._toggelItemEditMode($(element));
        });

        let $item = $(e.currentTarget);
        this._toggelItemEditMode($item);
    },
    _toggelItemEditMode($item) {
        if ($item.is('form')) {
            $item.prev().show();
            $item.remove();
        } else {
            let $form = this._getInputForm($item.data("value"), Boolean($item.data("can-delete")));
            $item.hide();
            $item.after($form);
        }
    },
    _getInputForm(inputVal, addDelete = false) {
        let $form = $('<form></form>', {"class": "edit-item-form form-inline d-inline-block pl-2 w-100"});

        let $inputGroup = $('<div></div>', {"class": "input-group input-group-sm"});
        $("<input>", {"type": "text", "class": "form-control edit-item-value", "value": inputVal}).appendTo($inputGroup);

        let $inputGroupAppend = $('<div></div>', {"class": "input-group-append"});
        $("<button type='submit' title='Save Changes' class='btn btn-outline-primary'><i class='fas fa-check fa-xs px-1'></i></button>").appendTo($inputGroupAppend);
        $("<button type='reset' title='Cancel' class='btn btn-outline-secondary'><i class='fas fa-times fa-xs px-1'></i></button>").appendTo($inputGroupAppend);
        if (addDelete) {
            $("<button type='delete' title='Delete Item' class='btn btn-outline-danger'><i class='fas fa-trash-alt fa-xs px-1'></i></button>").appendTo($inputGroupAppend);
        }

        $inputGroupAppend.appendTo($inputGroup);
        $inputGroup.appendTo($form);

        return $form;
    },
    _processInputFormAction(e) {
        e.preventDefault();
        let $btn = $(e.currentTarget);
        let $form = $btn.parents("form:first");
        const index = $form.parents(".list-group-item:first").data("index");

        if ($btn.attr("type") === "submit") {
            this.categoryList[index].name = $form.find("input.edit-item-value").val();
            this._synchroniseData();
        } else if ($btn.attr("type") === "delete") {
            if (Boolean(this.categoryList[index].can_delete))
                this.categoryList.splice(index, 1);
            this._synchroniseData();
        }
        this.draw();
    },
});

let showAreYouSureFunction = function (e) {
    var confirmationMessage = "You have unchanched things!";

    (e || window.event).returnValue = confirmationMessage; //Gecko + IE
    return confirmationMessage;                            //Webkit, Safari, Chrome
};

$(document).ready(() => {
    let categoryList = new SponsorCategoryList($('#categoryList'));
    var changeEvent = null;

    categoryList.dispatcher.on("changed", function (e) {
        if (changeEvent === null) {
            window.addEventListener("beforeunload", showAreYouSureFunction);
        }
    });

    $("#category_edit_form").on("submit", function (_) {
        window.removeEventListener("beforeunload", showAreYouSureFunction);
    });

    $("#new").on("click", function(e) {
        e.preventDefault();
        categoryList.addNew();
    });
});
