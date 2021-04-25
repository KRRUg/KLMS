const $ = require('jquery');
require('jquery-serializejson');
require('bootstrap');

let NavigationTree = function ($wrapper) {
    this.$root = $wrapper;
    this.dataSource = $wrapper.attr('data-source-input');
    this.maxDepth = $wrapper.attr('data-max-depth') || Number.MAX_VALUE;
    this.dispatcher = $({});

    this.initTree();
    this.drawTree();
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

$.extend(NavigationTree.prototype, {
    initTree() {
        let srcJSON = $(this.dataSource).val();
        this.navigationTree = JSON.parse(srcJSON);
    },
    drawTree() {
        this.$root.empty();
        this._buildTreeHTML(this.$root[ 0 ], this.navigationTree.children);

    },
    _buildTreeHTML(baseElement, navItems, index = "", level = 0) {
        let hasPrevItem = false;
        let hasNextItem = true;

        for (const [i, navItem] of navItems.entries()) {
            if (i === navItems.length - 1) {
                hasNextItem = false;
            }
            if (index === "") {
                var nextIndex = i;
            } else {
                var nextIndex = index + "_" + i;
            }
            let ele = this._buildTreeItemHTML(navItem, nextIndex, hasPrevItem, hasNextItem, level);
            baseElement.appendChild(ele);
            hasPrevItem = true;

            if (navItem.children !== '') {
                this._buildTreeHTML(baseElement, navItem.children, nextIndex, level + 1);
            }
    }
    },
    _buildTreeItemHTML(navItem, index, hasPrevItem = false, hasNextItem = false, level = 0) {
        let li = document.createElement("LI");
        li.setAttribute("class", "list-group-item d-flex");
        li.setAttribute("data-index", index);

        li.appendChild(this._buildActionElement("fas fa-arrow-up", "up", hasPrevItem, "Up"));
        li.appendChild(this._buildActionElement("fas fa-arrow-down", "down", hasNextItem, "Down"));
        li.appendChild(this._buildActionElement("fas fa-outdent", "outdent", 0 < level, "Outdent"));
        li.appendChild(this._buildActionElement("fas fa-indent", "indent", hasPrevItem && level + 1 < this.maxDepth, "Indent"));

        let label = document.createElement("SPAN");
        label.setAttribute("class", "w-25 nav-item-label");

        if (level > 0) {
            let i = document.createElement("I");
            i.setAttribute("class", "fas fa-share fa-flip-vertical fa-xs text-dark");
            i.setAttribute("style", "padding-left: " + (level * 2 + 0.5) + "rem;");
            label.appendChild(i);
        }


        let a = document.createElement("A");
        a.setAttribute("href", "#");
        a.setAttribute("class", "nav-item pl-2");
        a.setAttribute("title", "Edit Entry: " + navItem.name);
        a.setAttribute("data-path", navItem.path);
        a.setAttribute("data-value", navItem.name);
        a.textContent = navItem.name;

        let i = document.createElement("I");
        i.setAttribute("class", "fas fa-edit pl-2 edit-img text-dark");
        a.appendChild(i);
        label.appendChild(a);
        li.appendChild(label);
        
        if (navItem.path !== null) {
            li.appendChild(this._buildTypeElement(navItem.type, navItem.path));
        }

        return li;
    },
    _buildActionElement(itemImgClass, action, enableItem = false, title = "") {
        let i = document.createElement("I");
        i.setAttribute("class", itemImgClass + " fa-fw");

        if (enableItem) {
            var actionItem = document.createElement("A");
            actionItem.setAttribute("href", "#");
            actionItem.setAttribute("class", "nav-item-action");
            actionItem.setAttribute("title", title);
            actionItem.setAttribute("data-action", action);
        } else {
            var actionItem = document.createElement("SPAN");
            actionItem.setAttribute("class", "nav-item-action text-disabled disabled");
        }
        actionItem.appendChild(i);

        return actionItem;
    },
    _buildTypeElement(type, path) {
        let bgColor = "#6c757d";
        let color = "#fff";
        let symbole = "fas fa-link";
        
        switch (type) {
            case "path":
                if (path === '/') {
                    bgColor = "#e67925";
                    type = 'homepage';
                    symbole = "fas fa-home";
                } else {
                    bgColor = "#1962E6";
                    symbole = "fas fa-link";
                }
                break;
            case "content":
                bgColor = "#008799";
                symbole = "far fa-file-alt";
                break;
            case "teamsite":
                bgColor = "#9c08ff";
                symbole = "fas fa-sitemap";
                break;
            case "empty":
                break;
        }

        let badge = document.createElement("SPAN");
        badge.setAttribute("class", "badge badge-pill");
        badge.setAttribute("title", "Type: " + type);
        badge.setAttribute("style", "color: " + color + "; background-color: " + bgColor + ";");

        let sym = document.createElement("SPAN");
        sym.setAttribute("class", "fa-fw mr-1 " + symbole);
        badge.appendChild(sym);
        badge.appendChild(document.createTextNode(path));

        return badge;
    },
    _processNavigationAction(e) {
        e.preventDefault();
        let $actionButton = $(e.currentTarget);
        let action = $actionButton.data("action");
        let $navItem = $actionButton.parent();

        var curEle = this.navigationTree;
        let index = String($navItem.data("index"));

        var elements = [];
        var indizes = [];

        elements.push(curEle);
        indizes.push(0);

        for (const [_, searchIndex] of index.split("_").entries()) {
            indizes.push(parseInt(searchIndex));
            curEle = curEle.children[searchIndex];
            elements.push(curEle);
        }

        let curElem = elements[elements.length - 1];
        let parentChilds = elements[elements.length - 2].children;
        let pos = indizes[indizes.length - 1];

        switch (action) {
            case "up":
                [parentChilds[pos - 1], parentChilds[pos]] = [parentChilds[pos], parentChilds[pos - 1]];
                break;
            case "down":
                [parentChilds[pos + 1], parentChilds[pos]] = [parentChilds[pos], parentChilds[pos + 1]];
                break;
            case "indent":
                parentChilds[pos - 1].children.push(curElem);
                parentChilds.splice(pos, 1);
                break;
            case "outdent":
                let grandparentChilds = elements[elements.length - 3].children;
                grandparentChilds.splice(indizes[indizes.length - 2] + 1, 0, curElem);
                parentChilds.splice(indizes[indizes.length - 1], 1);
                break;
        }

        this._synchroniseData();
        this.dispatcher.trigger("changed");
        this.drawTree();
    },
    addNavItem(name, path, type) {
        let ele = {
            "name": name,
            "path": path,
            "type": type,
            children: []
        };

        this.navigationTree.children.push(ele);

        this._synchroniseData();
        this.dispatcher.trigger("changed");
    },
    _setNavItem(index, value) {
        var curEle = this.navigationTree;

        for (const [_, searchIndex] of String(index).split("_").entries()) {
            curEle = curEle.children[searchIndex];
        }

        curEle.name = value;
        this._synchroniseData();
        this.dispatcher.trigger("changed");
    },
    _deleteNavItem(index) {
        var curEle = this.navigationTree;
        var parent = curEle;
        var curIndex;

        for (const [_, searchIndex] of String(index).split("_").entries()) {
            curIndex = searchIndex;
            parent = curEle;
            curEle = curEle.children[searchIndex];
        }

        parent.children.splice(curIndex, 1);

        this._synchroniseData();
        this.dispatcher.trigger("changed");
    },
    _synchroniseData() {
        var json = JSON.stringify(this.navigationTree);
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
    _processInputFormAction(e) {
        e.preventDefault();
        let $btn = $(e.currentTarget);
        let $form = $btn.parents("form:first");

        if ($btn.attr("type") === "submit") {
            let val = $form.find("input.edit-item-value").val();
            let i = $form.parents(".list-group-item:first").data("index");
            this._setNavItem(i, val);
            this.drawTree();
        } else if ($btn.attr("type") === "delete") {
            let i = $form.parents(".list-group-item:first").data("index");
            this._deleteNavItem(i);
            this.drawTree();
        }

        this._toggelItemEditMode($form);
    },
    _toggelItemEditMode($item) {
        if ($item.is('form')) {
            $item.prev().show();
            $item.remove();
        } else {
            let $form = this._getInputForm($item.data("value"));
            $item.hide();
            $item.after($form);
        }
    },
    _getInputForm(inputVal) {
        let $form = $('<form></form>', {"class": "edit-item-form form-inline d-inline-block pl-2"});

        let $inputGroup = $('<div></div>', {"class": "input-group input-group-sm"});
        $("<input>", {"type": "text", "class": "form-control edit-item-value", "value": inputVal}).appendTo($inputGroup);

        let $inputGroupAppend = $('<div></div>', {"class": "input-group-append"});
        $("<button type='submit' title='Save Changes' class='btn btn-outline-primary'><i class='fas fa-check fa-xs px-1'></i></button>").appendTo($inputGroupAppend);
        $("<button type='reset' title='Cancel' class='btn btn-outline-secondary'><i class='fas fa-times fa-xs px-1'></i></button>").appendTo($inputGroupAppend);
        $("<button type='delete' title='Delete Item' class='btn btn-outline-danger'><i class='fas fa-trash-alt fa-xs px-1'></i></button>").appendTo($inputGroupAppend);

        $inputGroupAppend.appendTo($inputGroup);
        $inputGroup.appendTo($form);

        return $form;
    }
});

let showAreYouSureFunction = function (e) {
    var confirmationMessage = "You have unchanched things!";

    (e || window.event).returnValue = confirmationMessage; //Gecko + IE
    return confirmationMessage;                            //Webkit, Safari, Chrome
};

$(document).ready(() => {
    let navTree = new NavigationTree($('#navTree'));
    var changeEvent = null;

    navTree.dispatcher.on("changed", function (e) {
        if (changeEvent === null) {
            window.addEventListener("beforeunload", showAreYouSureFunction);
        }
    });

    $("#nav_edit_form").on("submit", function (_) {
        window.removeEventListener("beforeunload", showAreYouSureFunction);
    });

    $("#addNavItemModal").on("show.bs.modal", function (e) {
        let $target = $(e.currentTarget);
        selectAddDialogRow($target, "#add-dialog-choose-type");
        $target.find("form").trigger("reset");
    });

    $("#addNavItemModal .choose-type-btn").on("click", function (e) {
        e.preventDefault();
        let target = $(e.currentTarget).data("target");
        selectAddDialogRow($("#addNavItemModal"), target);
    });

    function selectAddDialogRow($modal, rowId) {
        $modal.find(".add-dialog-row:not(.d-none)").addClass("d-none");
        $modal.find(rowId).removeClass("d-none");

        if (rowId === "#add-dialog-choose-type") {
            $modal.find("button[type=submit]").addClass('disabled');
        } else {
            $modal.find("button[type=submit]").removeClass('disabled');
        }
    }
    
    $("#addNavItemModal").on("click", "button[type=submit]:not(.disabled)", function(e) {
        e.preventDefault();
        let $form = $("#addNavItemModal").find(".add-dialog-row:not(.d-none)").find("form:first");
        //To trigger HTML5 Form Validation with browser messages you have to click a submit button
        $('<input type="submit">').hide().appendTo($form).click().remove();
    });
    
    $("#addNavItemModal form").on("submit", function(e) {
        e.preventDefault();
        
        let formData = $(this).serializeArray().reduce(
        (obj, item) => Object.assign(obj, { [item.name]: item.value }), {});
        
        let type = $(this).data("type");
        let name = formData["navigation_node[name]"];
        let path = formData[`navigation_node[${type}]`] || null;
        
        navTree.addNavItem(name, path, type);
        navTree.drawTree();
        $("#addNavItemModal").modal('hide');
    });
});