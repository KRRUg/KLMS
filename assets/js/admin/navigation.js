const $ = require('jquery');
require('jquery-serializejson');
require('bootstrap');

let NavigationTree = function ($wrapper) {
    this.$root = $wrapper;
    this.dataSource = $wrapper.attr('data-source-input');
    
    this.initTree();
    this.drawTree();
    this.$root.on(
            'click',
            '.nav-item-action:not(.disabled)',
            this._processNavigationAction.bind(this)
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
        li.setAttribute("class", "list-group-item");
        li.setAttribute("data-index", index);

        if (level > 0) {
            let i = document.createElement("SPAN");
            i.setAttribute("class", "fa-xs");
            i.setAttribute("style", "padding-left: " + (level * 2.5 + 0.5) + "rem;");
            li.appendChild(i);
        }

        let prev = this._buildActionElement("fas fa-arrow-up", "up", hasPrevItem);
        li.appendChild(prev);
        let next = this._buildActionElement("fas fa-arrow-down", "down", hasNextItem);
        li.appendChild(next);



        let a = document.createElement("A");
        a.setAttribute("href", "#");
        a.setAttribute("class", "nav-item pl-2");
        a.setAttribute("data-path", navItem.path);
        a.textContent = navItem.name;
        li.appendChild(a);

        return li;
    },
    _buildActionElement(itemImgClass, action, enableItem = false) {
        let i = document.createElement("I");
        i.setAttribute("class", itemImgClass + " fa-fw");

        if (enableItem) {
            var actionItem = document.createElement("A");
            actionItem.setAttribute("href", "#");
            actionItem.setAttribute("class", "nav-item-action");
            actionItem.setAttribute("data-action", action);
        } else {
            var actionItem = document.createElement("SPAN");
            actionItem.setAttribute("class", "nav-item-action text-light disabled");
        }
        actionItem.appendChild(i);

        return actionItem;
    },
    _processNavigationAction(e) {
        e.preventDefault();
        let $actionButton = $(e.currentTarget);
        let action = $actionButton.data("action");
        let $navItem = $actionButton.parent();
        let index = String($navItem.data("index"));
        let indizes = index.split("_");
        var curElem = this.navigationTree.children;

        for (const [i, searchIndex] of indizes.entries()) {
            if (i === indizes.length - 1) {
                let si = parseInt(searchIndex);
                switch (action) {
                    case "up":
                        var h = curElem[si - 1];
                        curElem[si - 1] = curElem[si];
                        curElem[si] = h;
                        break;
                    case "down":
                        var h = curElem[si + 1];
                        curElem[si + 1] = curElem[si];
                        curElem[si] = h;
                        break;
                }
                break;
            }

            curElem = curElem[searchIndex].children;
        }
        this._synchroniseData();
        this.drawTree();
    },
    _synchroniseData() {
        var json = JSON.stringify(this.navigationTree);
        $(this.dataSource).val(json);
    }
});

$(document).ready(() => {
    new NavigationTree($('#navTree'));

    window.addEventListener("beforeunload", function (e) {
        var confirmationMessage = "\o/";

        (e || window.event).returnValue = confirmationMessage; //Gecko + IE
        return confirmationMessage;                            //Webkit, Safari, Chrome
    });
});