const $ = require('jquery');
require('jquery-serializejson');
require('bootstrap');

let TeamSiteAdmin = function ($wrapper) {
    this.$root = $wrapper;
    this.dataSource = $wrapper.attr('data-source-input');
    this.dispatcher = $({});

    this.initTSAdmin();
    this.drawTSAdmin();
    this.$root.on(
            'click',
            '.team-card-edit',
            this._toggleCardEditMode.bind(this)
            );

    this.$root.on(
            'click',
            '.team-card-delete',
            this._editItem.bind(this)
            );

    this.$root.on(
            'click',
            '.team-card-add',
            this._processInputFormAction.bind(this)
            );

    this.$root.on(
            'click',
            '.edit-item-form button',
            this._processInputFormAction.bind(this)
            );
};

$.extend(TeamSiteAdmin.prototype, {
    initTSAdmin() {
        let srcJSON = $(this.dataSource).val();
        this.teamSite = JSON.parse(srcJSON);
    },
    drawTSAdmin() {
        console.log(this.teamSite);
        this.$root.empty();
        for (let i = 0; i < this.teamSite.length; i++) {
            let section = this._generateSection(this.teamSite[i], i);
            this.$root[0].appendChild(section);
            this.$root[0].appendChild(document.createElement("HR"));
        }
    },
    _generateSection(sectionElement, index) {
        let section = document.createElement("SECTION");
        section.setAttribute("id", "team-section-"+index);
        section.setAttribute("class", "row team-section");
        section.setAttribute("data-index", index);

        let heading = document.createElement("H3");
        heading.setAttribute("class", "col-12");
        heading.setAttribute("data-parent", "team-section");
        heading.setAttribute("data-input-target", "title");
        heading.setAttribute("data-input-type", "text");
        
        heading.textContent = sectionElement.title;
        section.appendChild(heading);

        let description = document.createElement("P");
        description.setAttribute("class", "col-12");
        description.setAttribute("data-parent", "team-section");
        description.setAttribute("data-input-target", "description");
        description.setAttribute("data-input-type", "textarea");
        description.textContent = sectionElement.description;
        section.appendChild(description);

        let teamEntries = document.createElement("DIV");
        teamEntries.setAttribute("class", "row row-cols-1 row-cols-md-2 row-cols-lg-3 row-cols-xl-5");
        var i = 0;
        for (let sectionEntry of sectionElement.entries) {
            let entry = this._generateTeamEntry(sectionEntry, i, index);
            teamEntries.appendChild(entry);
            i++;
        }

        let entry = document.createElement("a");
        entry.setAttribute("class", "card team-card team-card-add text-center text-success");
        entry.setAttribute("data-index", index);
        entry.setAttribute("href", "#");

        let entryDetails = document.createElement("DIV");
        entryDetails.setAttribute("class", "card-body");
        entryDetails.innerHTML = '<h5><i class="fas fa-plus"></i></h5><p class="card-text">Teammitglied hinzufügen</span>';
        entry.appendChild(entryDetails);
        let entryWrap = document.createElement("DIV");
        entryWrap.setAttribute("class", "col mb-4");
        entryWrap.appendChild(entry);
        teamEntries.appendChild(entryWrap);

        let teamEntriesWrap = document.createElement("DIV");
        teamEntriesWrap.setAttribute("class", "col-12");
        teamEntriesWrap.appendChild(teamEntries);
        section.appendChild(teamEntriesWrap);

        return section;
    },
    _generateTeamEntry(teamEntry, index, parentIndex) {
        let eleIndex = parentIndex + '_' + index;
        let id = 'team-card-' + eleIndex;
        
        let entry = document.createElement("DIV");
        entry.setAttribute("id", id);
        entry.setAttribute("class", "card team-card");
        entry.setAttribute("data-index", eleIndex);

        let userDetails = document.createElement("DIV");
        userDetails.setAttribute("class", "card-body");

        let userDet = this._generateUserEntry(teamEntry.user);
        userDetails.appendChild(userDet);

        let userTitle = document.createElement("h4");
        userTitle.setAttribute("class", "card-title h5");
        userTitle.setAttribute("data-parent", "team-card");
        userTitle.setAttribute("data-input-target", "title");
        userTitle.setAttribute("data-input-type", "text");
        userTitle.textContent = teamEntry.title;
        userDetails.appendChild(userTitle);

        let userDesc = document.createElement("P");
        userDesc.setAttribute("class", "card-text");
        userDesc.setAttribute("data-parent", "team-card");
        userDesc.setAttribute("data-input-target", "description");
        userDesc.setAttribute("data-input-type", "textarea");
        userDesc.textContent = teamEntry.description;
        userDetails.appendChild(userDesc);

        entry.appendChild(userDetails);

        let footer = document.createElement("DIV");
        footer.setAttribute("class", "card-footer");
        footer.innerHTML = '<a href="#" class="team-card-edit" data-index="' + eleIndex + '" data-target="' + id + '"><i class="fas fa-edit"></i> Bearbeiten</a><a href="#" class="team-card-delete float-right text-danger" data-index="' + parentIndex + '_' + index + '"><i class="fas fa-trash"></i> Löschen</a>';
        entry.appendChild(footer);

        let entryWrap = document.createElement("DIV");
        entryWrap.setAttribute("class", "col mb-4");
        entryWrap.appendChild(entry);

        return entryWrap;
    },
    _generateUserEntry(user) {
        let userEntry = document.createElement("DIV");
        userEntry.setAttribute("class", "media mb-2");
        userEntry.setAttribute("data-parent", "team-card");
        userEntry.setAttribute("data-input-target", "user");
        userEntry.setAttribute("data-input-type", "user");

        if (user.image) {
            let userImg = document.createElement("IMG");
            userImg.setAttribute("class", "mr-2");
            userImg.setAttribute("style", "max-height:4rem;");
            userImg.setAttribute("src", user.image);
            userImg.setAttribute("alt", "User Image");
            userEntry.appendChild(userImg);
        }

        let bd = document.createElement("DIV");
        bd.setAttribute("class", "media-body");

        let userNickname = document.createElement("h5");
        userNickname.setAttribute("class", "mb-0");
        userNickname.textContent = user.nickname;
        bd.appendChild(userNickname);

        let userName = document.createElement("p");
        userName.setAttribute("class", "mb-0");
        userName.textContent = user.firstname + " " + user.surname;
        bd.appendChild(userName);

        userEntry.appendChild(bd);

        return userEntry;
    },
    _setTeamEntry() {
        
    },
    _toggleCardEditMode(e) {
        e.preventDefault();
        let target = $(e.currentTarget).data("target");
        let $items = $("#"+target).find('[data-parent="team-card"]');
        
        $items.each((_, element) => {
            this._toggleItemEditMode($(element));
        });
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
    _toggleItemEditMode($item) {
        let type = $item.data("inputType");
        let inputTarget = $item.data("inputTarget");
        let val = "";
        
        switch(type) {
            case "textarea":
                val = this._toogleTextAreaEdit($item);
                break;
            case "user":
                break;
            default:
                val = this._toogleTextEdit($item);
        }
        /*
        if ($item.is('form')) {
            $item.prev().show();
            $item.remove();
        } else {
            let $form = this._getInputForm($item.data("value"));
            $item.hide();
            $item.after($form);
        }
         */
    },
    _toogleTextEdit($item) {
        if($item.is('input')) {
            let $wrap = $item.parents("div.form-group").first();
            let val = $item.val();
            
            $wrap.prev().show();
            $wrap.remove();
            
            return val;
        } else {
            let $inputGroup = $('<div></div>', {"class": "form-group"});
            $("<label></label>").text($item.data("inputTarget")).appendTo($inputGroup);
            $("<input>", {"type": "text", "class": "form-control edit-item-value", "value": $item.text(), "name": $item.data("inputTarget"),"data-input-type": $item.data("inputType"),"data-parent": $item.data("parent")}).appendTo($inputGroup);
            $item.hide();
            $item.after($inputGroup);
        }
        
        return null;
    },
    _toogleTextAreaEdit($item) {
        if($item.is('input')) {
            let $wrap = $item.parents("div.form-group").first();
            let val = $item.val();
            
            $wrap.prev().show();
            $wrap.remove();
            
            return val;
        } else {
            let $inputGroup = $('<div></div>', {"class": "form-group"});
            $("<label></label>").text($item.data("inputTarget")).appendTo($inputGroup);
            $("<textarea></textarea>", {"type": "text", "class": "form-control edit-item-value", "name": $item.data("inputTarget"),"data-input-type": $item.data("inputType"),"data-parent": $item.data("parent")}).text($item.text()).appendTo($inputGroup);
            $item.hide();
            $item.after($inputGroup);
        }
        
        return null;
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
    let teamSiteAdmin = new TeamSiteAdmin($('#teamSiteAdmin'));
    var changeEvent = null;

    teamSiteAdmin.dispatcher.on("changed", function (e) {
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

    $("#addNavItemModal").on("click", "button[type=submit]:not(.disabled)", function (e) {
        e.preventDefault();
        let $form = $("#addNavItemModal").find(".add-dialog-row:not(.d-none)").find("form:first");
        //To trigger HTML5 Form Validation with browser messages you have to click a submit button
        $('<input type="submit">').hide().appendTo($form).click().remove();
    });

    $("#addNavItemModal form").on("submit", function (e) {
        e.preventDefault();

        let formData = $(this).serializeArray().reduce(
                (obj, item) => Object.assign(obj, {[item.name]: item.value}), {});

        let type = $(this).data("type");
        let name = formData["navigation_node[name]"];
        let path = formData[`navigation_node[${type}]`] || null;

        teamSiteAdmin.addNavItem(name, path, type);
        teamSiteAdmin.drawTree();
        $("#addNavItemModal").modal('hide');
    });
});