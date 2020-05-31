const $ = require('jquery');
require('jquery-serializejson');
require('bootstrap');

let NavigationTree = function($wrapper) {
    this.$root = $wrapper;
    this.updateTree();
};

$.extend(NavigationTree.prototype, {
    updateTree() {
        this._loadData()
            .then((data) => this._fillTree(data))
            .catch((error) => { console.log(error); });
    },

    _fillTree(data) {
        this.$root.empty();
        this.$root.append(this.__processRootNode(data));
    },

    __processRootNode(data) {
        let $ul = $("<ul>");
        $ul.addClass('tree');
        for (el of data.children) {
            $ul.append(this.__processNode(el));
        }
        return $ul;
    },

    __processNode(data) {
        let $li = $('<li>');
        $li.append($(`<span>${data.name}</span>`));
        let $div = $("<div>");
        let $up = $("<button>&#x25b2;</button>");
        let $down = $("<button>&#x25bc;</button>");
        $div.append($up);
        $div.append($down);
        $up.on('click', () => _postMove(data.id, null, data.order-1));
        $down.on('click', () => _postMove(data.id, null, data.order+1));

        let $ul = $('<ul>');
        for (el of data.children) {
            $ul.append(this.__processNode(el));

        }
        $li.append($ul);
        return $li;
    },

    _postMove(id, parent, pos) {

    },

    _loadData() {
        let url = window.location.href + '.json';
        return new Promise((resolve, reject) => {
            $.ajax({
                url: url,
                method: 'GET',
                dataType: 'json'
            }).then((data) => {
                resolve(data);
            }).catch((jqXHR) => {
                let errorData = JSON.parse(jqXHR.responseText);
                reject(errorData.errors);
            });
        });
    }
});

$(document).ready(() => {
    new NavigationTree($('#navTree'));
});