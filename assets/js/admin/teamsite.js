const $ = require('jquery');
require('jquery-serializejson');
require('bootstrap');

let TeamSiteAdmin = function ($wrapper) {
    this.$root = $wrapper;
    this.dataSource = $wrapper.attr('data-source-input');
    this.userQuerySource = $wrapper.attr('data-user-remote-target');
    this.dispatcher = $({});

    this.initTSAdmin();
    this.drawTSAdmin();
    
    this.$root.on(
            'click',
            '.team-section-action',
            this._processTeamCardAction.bind(this)
            );
    
    this.$root.on(
            'click',
            '.team-section-add',
            this.appendSection.bind(this)
            );
    
    this.$root.on(
            'click',
            '.team-card-action',
            this._processTeamCardAction.bind(this)
            );
    this.$root.on(
            'click',
            '.team-card-add',
            this._processTeamCardAction.bind(this)
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
        let id = 'team-section-' + index;
        
        let section = document.createElement("SECTION");
        section.setAttribute("id", id);
        section.setAttribute("class", "row team-section");
        section.setAttribute("data-index", index);
        section.setAttribute("data-wrap", "team-section");

        
        let editArea = document.createElement("DIV");
        editArea.setAttribute("class", "col-12 pb-3");
        let editAreaHTML = '<a href="#" class="team-section-action action-btn mr-4" data-action="edit" data-index="' + index + '" data-target="' + id + '"><i class="fas fa-edit"></i> Bearbeiten</a>';
        editAreaHTML +=    '<a href="#" class="team-section-action action-btn text-danger" data-action="delete" data-index="'+ index + '"  data-target="' + id + '"><i class="fas fa-trash"></i> Löschen</a>';
        editAreaHTML +=    '<a href="#" class="team-section-action action-btn mr-4 text-success hidden" data-action="submit" data-index="' + index + '" data-target="' + id + '" style="display: none;"><i class="fas fa-check"></i> Änderungen übernehmen</a>';
        editAreaHTML +=    '<a href="#" class="team-section-action action-btn text-secondary hidden" data-action="cancel" data-index="' + index + '" data-target="' + id + '" style="display: none;"><i class="fas fa-times"></i> Abbrechen</a>';
        editArea.innerHTML = editAreaHTML;
        section.appendChild(editArea);

        let heading = document.createElement("H3");
        heading.setAttribute("class", "col-12");
        heading.setAttribute("data-parent", "team-section");
        heading.setAttribute("data-input-target", "title");
        heading.setAttribute("data-input-type", "text");
        
        heading.textContent = sectionElement.title;
        section.appendChild(heading);
        section.appendChild(document.createElement("BR"));

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
        entry.setAttribute("data-toggle", "modal");
        entry.setAttribute("href", "#addTeamMemberModal");

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
        entry.setAttribute("data-wrap", "team-card");

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
        let footerHTML = '<a href="#" class="team-card-action action-btn" data-action="edit" data-index="' + eleIndex + '" data-target="' + id + '"><i class="fas fa-edit"></i> Bearbeiten</a><a href="#" class="team-card-action action-btn float-right text-danger" data-action="delete" data-index="' + parentIndex + '_' + index + '"  data-target="' + id + '"><i class="fas fa-trash"></i> Löschen</a>';
        footerHTML +=    '<a href="#" class="team-card-action action-btn text-success hidden" data-action="submit" data-index="' + eleIndex + '" data-target="' + id + '" style="display: none;"><i class="fas fa-check"></i> Änderungen übernehmen</a><a href="#" class="team-card-action action-btn float-right text-secondary hidden" data-action="cancel" data-index="' + parentIndex + '_' + index + '" data-target="' + id + '" style="display: none;"><i class="fas fa-times"></i> Abbrechen</a>';
        footer.innerHTML = footerHTML;
        
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
        userEntry.setAttribute("data-input-value", user.uuid);

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
    _processTeamCardAction(e) {
        e.preventDefault();
        let target = $(e.currentTarget).data("target");
        let action = $(e.currentTarget).data("action");
        let $card = $("#"+target);

        switch(action) {
            case "edit":
                this._toggleCardEditMode($card);
                this.$root.find('a.action-btn').addClass('disabled');
                break;
            case "cancel":
                this._toggleCardEditMode($card);
                this.$root.find('a.action-btn').removeClass('disabled');
                break;
            case "delete":
                this._deleteCard($card);
                break;
            case "submit":
                this._submitCard($card);
                break;
        }
    },
    _toggleCardEditMode($card) {
        let parent = $card.data("wrap");
        let $items = $card.find('[data-parent="'+ parent +'"]').not(".hidden");
        
        $items.each((_, element) => {
            this._toggleItemEditMode($(element));
        });

        $card.find('a.action-btn').toggle();
    },
    _submitCard($card) {
        let cardIndex = String($card.data("index"));
        let index = cardIndex.includes("_") ? cardIndex.split("_") : cardIndex;
        let parent = $card.data("wrap");
        let $items = $card.find('[data-parent="'+ parent +'"]').not(".hidden");
        
        let ele = Array.isArray(index) ? this.teamSite[index[0]].entries[index[1]] : this.teamSite[index];
        
        $items.each((_, element) => {
            let name = element.getAttribute("name");
            let val = this._toggleItemEditMode($(element));
            
            if(!name) {
                return;
            }
            
            ele[name] = val;
        });

        this.refresh();
    },
    _deleteCard($card) {
        let cardIndex = String($card.data("index"));
        let index = cardIndex.includes("_") ? cardIndex.split("_") : cardIndex;
        let ele = this.teamSite;

        if(Array.isArray(index)) {
            let area = ele[index[0]];
            area.entries.splice(index[1], 1);
        } else {
            ele.splice(index, 1);
        }
        
        this.refresh();
    },
    refresh() {
        this._synchroniseData();
        this.drawTSAdmin();
    },
    _synchroniseData() {
        let json = JSON.stringify(this.teamSite);
        $(this.dataSource).val(json);
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
        
        return val;
    },
    _toogleTextEdit($item) {
        if($item.is('input')) {
            let $wrap = $item.parents("div.form-group").first();
            let val = $item.val();
            $wrap.prev().removeClass("hidden");
            $wrap.prev().show();
            $wrap.remove();
            
            return val;
        } else {
            let addClass = $item.hasClass("col-12") ? " col-12" : "";
            
            let $inputGroup = $('<div></div>', {"class": "form-group" + addClass});
            let targetText = $item.data("inputTarget");
            let labelText = targetText.charAt(0).toUpperCase() + targetText.slice(1);
            $("<label></label>").text(labelText).appendTo($inputGroup);
            $("<input>", {"type": "text", "class": "form-control edit-item-value", "value": $item.text(), "name": $item.data("inputTarget"),"data-input-type": $item.data("inputType"),"data-parent": $item.data("parent")}).appendTo($inputGroup);
            $item.addClass("hidden");
            $item.hide();
            $item.after($inputGroup);
        }
        
        return null;
    },
    _toogleTextAreaEdit($item) {
        if($item.is('textarea')) {
            let $wrap = $item.parents("div.form-group").first();
            let val = $item.val();
            $wrap.prev().removeClass("hidden");
            $wrap.prev().show();
            $wrap.remove();
            
            return val;
        } else {
            let addClass = $item.hasClass("col-12") ? " col-12" : "";
            
            let $inputGroup = $('<div></div>', {"class": "form-group" + addClass});
            let targetText = $item.data("inputTarget");
            let labelText = targetText.charAt(0).toUpperCase() + targetText.slice(1);
            $("<label></label>").text(labelText).appendTo($inputGroup);
            $("<textarea></textarea>", {"type": "text", "class": "form-control edit-item-value", "name": $item.data("inputTarget"),"data-input-type": $item.data("inputType"),"data-parent": $item.data("parent"), "rows":10}).text($item.text()).appendTo($inputGroup);
            $item.addClass("hidden");
            $item.hide();
            $item.after($inputGroup);
        }
        
        return null;
    },
    appendSection() {
        let newSection = {title: "", description: "", entries: []};
        this.teamSite.push(newSection);

        this.refresh();
        let ele = $('.team-section-action[data-action="edit"]').last();
        $(window).animate({scrollTop:ele.offset().top});
        ele.click();
    },
    addTeamMember(index, user) {
        let teamMember = {
            title: "",
            description: "",
            user: user
        };

        this.teamSite[index].entries.push(teamMember);
        this.refresh();
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

    $("#addTeamMemberModal").on("show.bs.modal", function (e) {
        let index = $(e.relatedTarget).data("index");
        let $target = $(e.currentTarget);
        let form = $target.find("form");
        form.trigger("reset");
        form.find('select.select2-enable').trigger('change');
        form.find('input[name="index"]').val(index);
    });

    $(".team-section-add").on("click", function(e) {
        e.preventDefault();
        teamSiteAdmin.appendSection();
    });

    $("#addTeamMemberModal form").on("submit", function (e) {
        e.preventDefault();

        let index = $(this).find('input[name="index"]').val();
        let selectedUserData = $(this).find('select.select2-enable').select2('data');
        let user = selectedUserData[0].user

        if(user) {
            teamSiteAdmin.addTeamMember(index, user);
        }

        $("#addTeamMemberModal").modal('hide');
    });
});