const $ = require('jquery');
require('jquery-serializejson');
require('bootstrap');

let NavigationTree = function ($wrapper) {
    this.$root = $wrapper;
    this.dataSource = $wrapper.attr('data-source-input');
    this.maxDepth = $wrapper.attr('data-max-depth') || Number.MAX_VALUE;
    this.wasChanged = false;

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

        let prev = this._buildActionElement("fas fa-arrow-up", "up", hasPrevItem, "Up");
        li.appendChild(prev);
        let next = this._buildActionElement("fas fa-arrow-down", "down", hasNextItem, "Down");
        li.appendChild(next);
        let outdent = this._buildActionElement("fas fa-outdent", "outdent", 0 < level, "Outdent");
        li.appendChild(outdent);
        let indent = this._buildActionElement("fas fa-indent", "indent", hasPrevItem && level + 1 < this.maxDepth, "Indent");
        li.appendChild(indent);

        if (level > 0) {
            let i = document.createElement("I");

            i.setAttribute("class", "fas fa-share fa-flip-vertical fa-xs text-dark");
            i.setAttribute("style", "padding-left: " + (level * 2 + 0.5) + "rem;");
            li.appendChild(i);
        }


        let a = document.createElement("A");
        a.setAttribute("href", "#");
        a.setAttribute("class", "nav-item pl-2");
        a.setAttribute("data-path", navItem.path);
        a.textContent = navItem.name;
        li.appendChild(a);

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

        this.wasChanged = true;
        this._synchroniseData();
        this.drawTree();
    },
    _synchroniseData() {
        var json = JSON.stringify(this.navigationTree);
        $(this.dataSource).val(json);
    }
});

$(document).ready(() => {
    let navTree = new NavigationTree($('#navTree'));

    let showAreYouSureFunction = function (e) {
        if (!navTree.wasChanged) {
            return;
        }

        var confirmationMessage = "You have unchanched things!";

        (e || window.event).returnValue = confirmationMessage; //Gecko + IE
        return confirmationMessage;                            //Webkit, Safari, Chrome
    };

    window.addEventListener("beforeunload", showAreYouSureFunction);
    
    $("#nav_edit_form").on("submit", function(_) {
        window.removeEventListener("beforeunload", showAreYouSureFunction);
    });
});