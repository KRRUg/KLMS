const $ = require('jquery');
require('jquery-serializejson');
require('bootstrap');

let TeamSiteAdmin = function ($wrapper) {
    this.$root = $wrapper;
    this.dataSource = $wrapper.attr('data-source-input');
    this.dispatcher = $({});

    this.initTSAdmin();
    this.drawTSAdmin();
    /*this.$root.on(
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
            );*/
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
        section.setAttribute("class", "row");
        section.setAttribute("data-index", index);
        
        let heading = document.createElement("H3");
        heading.setAttribute("class", "col-12");
        heading.textContent = sectionElement.title;
        section.appendChild(heading);
        
        let description = document.createElement("P");
        description.setAttribute("class", "col-12");
        description.textContent = sectionElement.description;
        section.appendChild(description);
        
        let teamEntries = document.createElement("DIV");
        teamEntries.setAttribute("class", "row row-cols-1 row-cols-md-2 row-cols-lg-3 row-cols-xl-5");
        var i = 0;
        for (let sectionEntry of sectionElement.entries) {
            let entry = this._generateTeamEntry(sectionEntry, i);
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
    _generateTeamEntry(teamEntry, index) {
        let entry = document.createElement("DIV");
        entry.setAttribute("class", "card team-card");
        entry.setAttribute("data-index", index);
        
        let userImg = document.createElement("IMG");
        userImg.setAttribute("class", "card-img-top");
        userImg.setAttribute("alt", "User Image");
        entry.appendChild(userImg);
        
        let userDetails = document.createElement("DIV");
        userDetails.setAttribute("class", "card-body");
        
        let userTitle = document.createElement("h3");
        userTitle.setAttribute("class", "card-title h5");
        userTitle.textContent = teamEntry.title;
        userDetails.appendChild(userTitle);
        
        let userDesc = document.createElement("P");
        userDesc.setAttribute("class", "card-text");
        userDesc.textContent = teamEntry.description;
        userDetails.appendChild(userDesc);
        
        entry.appendChild(userDetails);
        
        let footer = document.createElement("DIV");
        footer.setAttribute("class", "card-footer");
        footer.innerHTML = '<a href="#" class="team-card-edit" data-index="'+index+'"><i class="fas fa-edit"></i> Bearbeiten</a><a href="#" class="team-card-delete float-right text-danger" data-index="'+index+'"><i class="fas fa-trash"></i> Löschen</a>';
        entry.appendChild(footer);
        
        let entryWrap = document.createElement("DIV");
        entryWrap.setAttribute("class", "col mb-4");
        entryWrap.appendChild(entry);
        
        return entryWrap;
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
        
        teamSiteAdmin.addNavItem(name, path, type);
        teamSiteAdmin.drawTree();
        $("#addNavItemModal").modal('hide');
    });
});