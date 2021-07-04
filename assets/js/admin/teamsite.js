const $ = require('jquery');
require('jquery-serializejson');
import Sortable from 'sortablejs';
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
        let hideEmailStatus = sectionElement.hideEmail ? '✔' : '❌';
        let hideNameStatus = sectionElement.hideName ? '✔' : '❌';

        let section = document.createElement("SECTION");
        section.setAttribute("id", id);
        section.setAttribute("class", "row team-section");
        section.setAttribute("data-index", index);
        section.setAttribute("data-wrap", "team-section");


        let editArea = document.createElement("DIV");
        editArea.setAttribute("class", "col-12 pb-3");
        let editAreaHTML = '<a href="#" class="team-section-action action-btn mr-4" data-action="edit" data-index="' + index + '" data-target="' + id + '"><i class="fas fa-edit"></i> Bearbeiten</a>';
        editAreaHTML += '<a href="#" class="team-section-action action-btn text-danger" data-action="delete" data-index="' + index + '"  data-target="' + id + '"><i class="fas fa-trash"></i> Löschen</a>';
        editAreaHTML += '<a href="#" class="team-section-action action-btn mr-4 text-success hidden" data-action="submit" data-index="' + index + '" data-target="' + id + '" style="display: none;"><i class="fas fa-check"></i> Änderungen übernehmen</a>';
        editAreaHTML += '<a href="#" class="team-section-action action-btn text-secondary hidden" data-action="cancel" data-index="' + index + '" data-target="' + id + '" style="display: none;"><i class="fas fa-times"></i> Abbrechen</a>';
        editAreaHTML += '<a href="#" class="team-section-action badge badge-pill ' + this._getBadgeColor(sectionElement.hideEmail) + '" data-value="' + sectionElement.hideEmail + '" data-action="hideEmail" data-index="' + index + '" data-target="' + id + '" style="margin-left: 10px; pointer-events: none; cursor: default;">E-Mail verstecken: ' + hideEmailStatus + '</a>';
        editAreaHTML += '<a href="#" class="team-section-action badge badge-pill ' + this._getBadgeColor(sectionElement.hideName) + '" data-value="' + sectionElement.hideName + '" data-action="hideName" data-index="' + index + '" data-target="' + id + ' " style="margin-left: 10px; pointer-events: none; cursor: default;">Vor-/Nachnamen verstecken: ' + hideNameStatus + '</a>';
        editArea.innerHTML = editAreaHTML;
        section.appendChild(editArea);


        let hideEmail = document.createElement("input");
        hideEmail.setAttribute("style", "display: none;");
        hideEmail.setAttribute("type", "checkbox");
        hideEmail.setAttribute("class", "col-12");
        hideEmail.setAttribute("disabled", "true");
        if (sectionElement.hideEmail) {
            hideEmail.setAttribute("checked", "true");
        }
        hideEmail.setAttribute("data-parent", "team-section");
        hideEmail.setAttribute("data-input-target", "hideEmail");
        hideEmail.setAttribute("data-input-type", "checkbox");
        section.appendChild(hideEmail);


        let hideName = document.createElement("input");
        hideName.setAttribute("style", "display: none;");
        hideName.setAttribute("type", "checkbox");
        hideName.setAttribute("class", "col-12");
        hideName.setAttribute("disabled", "true");
        hideName.setAttribute("data-parent", "team-section");
        hideName.setAttribute("data-input-target", "hideName");
        hideName.setAttribute("data-input-type", "checkbox");
        if (sectionElement.hideName) {
            hideName.setAttribute("checked", "true");
        }
        section.appendChild(hideName);

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
        teamEntries.setAttribute("class", "row row-cols-1 row-cols-md-2 row-cols-lg-3 row-cols-xl-5 sortable");
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
        entryWrap.setAttribute("class", "col mb-4 sortable-ignore");
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

        let userDet = this._generateUserEntry(teamEntry.user, teamEntry.displayEmail);
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

        let displayEmail = document.createElement("P");
        displayEmail.setAttribute("class", "card-text");
        //Hide the Field in the Preview, so it's only shown in the Edit Window
        displayEmail.setAttribute("style", "display: none;");
        displayEmail.setAttribute("data-parent", "team-card");
        displayEmail.setAttribute("data-input-target", "displayEmail");
        displayEmail.setAttribute("data-input-type", "hiddentext");
        displayEmail.textContent = teamEntry.displayEmail;
        userDetails.appendChild(displayEmail);

        entry.appendChild(userDetails);

        let footer = document.createElement("DIV");
        footer.setAttribute("class", "card-footer");
        let footerHTML = '<a href="#" class="team-card-action action-btn" data-action="edit" data-index="' + eleIndex + '" data-target="' + id + '"><i class="fas fa-edit"></i> Bearbeiten</a><a href="#" class="team-card-action action-btn float-right text-danger" data-action="delete" data-index="' + parentIndex + '_' + index + '"  data-target="' + id + '"><i class="fas fa-trash"></i> Löschen</a>';
        footerHTML += '<a href="#" class="team-card-action action-btn text-success hidden" data-action="submit" data-index="' + eleIndex + '" data-target="' + id + '" style="display: none;"><i class="fas fa-check"></i> Änderungen übernehmen</a><a href="#" class="team-card-action action-btn float-right text-secondary hidden" data-action="cancel" data-index="' + parentIndex + '_' + index + '" data-target="' + id + '" style="display: none;"><i class="fas fa-times"></i> Abbrechen</a>';
        footer.innerHTML = footerHTML;

        entry.appendChild(footer);

        let entryWrap = document.createElement("DIV");
        entryWrap.setAttribute("class", "col mb-4");
        entryWrap.appendChild(entry);

        return entryWrap;
    },
    _generateUserEntry(user, displayEmail) {
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

        //Test if displayEmail is set
        if (displayEmail) {
            let email = document.createElement("p");
            email.setAttribute("class", "mb-0");
            email.textContent = displayEmail;
            bd.appendChild(email);
        } else {
            let email = document.createElement("p");
            email.setAttribute("class", "mb-0");
            email.textContent = user.email;
            bd.appendChild(email);
        }

        userEntry.appendChild(bd);

        return userEntry;
    },
    _processTeamCardAction(e) {
        e.preventDefault();
        let target = $(e.currentTarget).data("target");
        let action = $(e.currentTarget).data("action");
        let $card = $("#" + target);

        switch (action) {
            case "edit":
                this._toggleCardEditMode($card);
                this.$root.find('a.action-btn').addClass('disabled');
                this.$root.find('a.badge').attr('style', 'margin-left: 10px;');
                break;
            case "cancel":
                this._toggleCardEditMode($card);
                this.$root.find('a.action-btn').removeClass('disabled');
                this.$root.find('a.badge').attr('style', 'margin-left: 10px; pointer-events: none; cursor: default;');
                this._resetBadgeState($card);
                break;
            case "delete":
                this._deleteCard($card);
                break;
            case "submit":
                this._submitCard($card);
                break;
            case "hideEmail":
                this._toggleHideEmail($(e.currentTarget));
                break;
            case "hideName":
                this._toggleHideName($(e.currentTarget));
                break;
        }
    },
    _toggleCardEditMode($card) {
        let parent = $card.data("wrap");
        let $items = $card.find('[data-parent="' + parent + '"]').not(".hidden");

        $items.each((_, element) => {
            this._toggleItemEditMode($(element));
        });

        $card.find('a.action-btn').toggle();
    },
    _submitCard($card) {
        let cardIndex = String($card.data("index"));
        let index = cardIndex.includes("_") ? cardIndex.split("_") : cardIndex;
        let parent = $card.data("wrap");
        let $items = $card.find('[data-parent="' + parent + '"]').not(".hidden");

        let ele = Array.isArray(index) ? this.teamSite[index[0]].entries[index[1]] : this.teamSite[index];

        $items.each((_, element) => {
            let name = element.getAttribute("name");
            let val = this._toggleItemEditMode($(element));

            if (!name) {
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

        if (Array.isArray(index)) {
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
        this.createSortable();
    },
    _synchroniseData() {
        let json = JSON.stringify(this.teamSite);
        $(this.dataSource).val(json);
        this.dispatcher.trigger("changed");
    },
    _toggleItemEditMode($item) {
        let type = $item.data("inputType");
        let inputTarget = $item.data("inputTarget");
        let val = "";

        switch (type) {
            case "textarea":
                val = this._toogleTextAreaEdit($item);
                break;
            case "user":
                break;
            case "checkbox":
                val = this._toggleCheckboxEdit($item);
                break;
            default:
                val = this._toogleTextEdit($item);
        }
        return val;
    },
    _toggleCheckboxEdit($item) {
        if ($item.is('input:enabled')) {
            //Happens when Saving
            let $wrap = $item.parents("div.form-group").first();
            let val = $item.prop("checked");
            $wrap.prev().removeClass("hidden");
            $item.prop("disabled", false);
            //$wrap.prev().show();
            $wrap.remove();

            return val;
        } else {
            //Happens when pressing "edit"
            let addClass = $item.hasClass("col-12") ? " col-12" : "";

            let $inputGroup = $('<div></div>', {"class": "form-group" + addClass}).attr("style", "display: none;");
            let targetText = $item.data("inputTarget");
            let labelText = targetText.charAt(0).toUpperCase() + targetText.slice(1);
            $("<label></label>").text(labelText).appendTo($inputGroup);
            if ($item.prop("checked")) {
                $("<input>", {
                    "type": "checkbox",
                    "class": "form-control edit-item-value",
                    "value": "true",
                    "name": $item.data("inputTarget"),
                    "data-input-type": $item.data("inputType"),
                    "data-parent": $item.data("parent"),
                    "checked": "true"
                }).appendTo($inputGroup);

            } else {
                $("<input>", {
                    "type": "checkbox",
                    "class": "form-control edit-item-value",
                    "value": "true",
                    "name": $item.data("inputTarget"),
                    "data-input-type": $item.data("inputType"),
                    "data-parent": $item.data("parent")
                }).appendTo($inputGroup);

            }
            $item.addClass("hidden");
            $item.prop("disabled", true);
            $item.hide();
            $item.after($inputGroup);
        }

        return null;
    },
    _toogleTextEdit($item) {
        if ($item.is('input')) {
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
            $("<input>", {
                "type": "text",
                "class": "form-control edit-item-value",
                "value": $item.text(),
                "name": $item.data("inputTarget"),
                "data-input-type": $item.data("inputType"),
                "data-parent": $item.data("parent")
            }).appendTo($inputGroup);
            $item.addClass("hidden");
            $item.hide();
            $item.after($inputGroup);
        }

        return null;
    },
    _toogleTextAreaEdit($item) {
        if ($item.is('textarea')) {
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
            $("<textarea></textarea>", {
                "type": "text",
                "class": "form-control edit-item-value",
                "name": $item.data("inputTarget"),
                "data-input-type": $item.data("inputType"),
                "data-parent": $item.data("parent"),
                "rows": 10
            }).text($item.text()).appendTo($inputGroup);
            $item.addClass("hidden");
            $item.hide();
            $item.after($inputGroup);
        }

        return null;
    },
    _toggleHideEmail($item) {
        if ($item.data('value') === true) {
            let $hiddenCheckbox = $($item).parent().parent().find('input[name="hideEmail"].edit-item-value');
            $hiddenCheckbox.removeAttr('checked');
            $item.attr('data-value', false);
            $item.data('value', false);
            $item.removeClass('badge-primary');
            $item.addClass('badge-secondary');
            $item.text('E-Mail verstecken: ❌');
        } else {
            let $hiddenCheckbox = $($item).parent().parent().find('input[name="hideEmail"].edit-item-value');
            $hiddenCheckbox.attr('checked', '');
            $item.attr('data-value', true);
            $item.data('value', true);
            $item.removeClass('badge-secondary');
            $item.addClass('badge-primary');
            $item.text('E-Mail verstecken: ✔');
        }

        return null;
    },
    _toggleHideName($item) {
        if ($item.data('value') === true) {
            let $hiddenCheckbox = $($item).parent().parent().find('input[name="hideName"].edit-item-value');
            $hiddenCheckbox.removeAttr('checked');
            $item.attr('data-value', false);
            $item.data('value', false);
            $item.removeClass('badge-primary');
            $item.addClass('badge-secondary');
            $item.text('Vor-/Nachnamen verstecken: ❌');
        } else {
            let $hiddenCheckbox = $($item).parent().parent().find('input[name="hideName"].edit-item-value');
            $hiddenCheckbox.attr('checked', '');
            $item.attr('data-value', true);
            $item.data('value', true);
            $item.removeClass('badge-secondary');
            $item.addClass('badge-primary');
            $item.text('Vor-/Nachnamen verstecken: ✔');
        }

        return null;
    },
    _getBadgeColor($v) {
        if ($v) {
            return "badge-primary";
        } else {
            return "badge-secondary";
        }
    },
    _resetBadgeState($card) {
      let $emailBadge = $card.find('a[data-action="hideEmail"].team-section-action');
      let $nameBadge = $card.find('a[data-action="hideName"].team-section-action');
      let $emailCheckbox = $card.find('input[data-input-target="hideEmail"]');
      let $nameCheckbox = $card.find('input[data-input-target="hideName"]');

      if($nameCheckbox.prop("checked")) {
          $nameBadge.attr('data-value', true);
          $nameBadge.data('value', true);
          $nameBadge.removeClass('badge-secondary');
          $nameBadge.addClass('badge-primary');
          $nameBadge.text('Vor-/Nachnamen verstecken: ✔');
      } else {
          $nameBadge.attr('data-value', false);
          $nameBadge.data('value', false);
          $nameBadge.removeClass('badge-primary');
          $nameBadge.addClass('badge-secondary');
          $nameBadge.text('Vor-/Nachnamen verstecken: ❌');
      }

        if($emailCheckbox.prop("checked")) {
            $emailBadge.attr('data-value', true);
            $emailBadge.data('value', true);
            $emailBadge.removeClass('badge-secondary');
            $emailBadge.addClass('badge-primary');
            $emailBadge.text('E-Mail verstecken: ✔');
        } else {
            $emailBadge.attr('data-value', false);
            $emailBadge.data('value', false);
            $emailBadge.removeClass('badge-primary');
            $emailBadge.addClass('badge-secondary');
            $emailBadge.text('E-Mail verstecken: ❌');
        }

        return null;
    },
    appendSection() {
        let newSection = {title: "", description: "", entries: []};
        this.teamSite.push(newSection);

        this.refresh();
        let ele = $('.team-section-action[data-action="edit"]').last();
        $(window).animate({scrollTop: ele.offset().top});
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
    },
    createSortable() {
        const elements = document.querySelectorAll('.sortable');
        Array.from(elements).forEach((element, index) => {
            var self = this;
            Sortable.create(element, {
                group: "teamsite-" + index,
                sort: true,
                filter: ".sortable-ignore",
                //draggable: ".team-card",  // Specifies which items inside the element should be draggable
                //onEnd: function (/**Event*/evt) {
                onSort: function (/**Event*/evt) {
                    var itemEl = evt.item;  // dragged HTMLElement
                    evt.to;    // target list
                    evt.from;  // previous list
                    evt.oldIndex;  // element's old index within old parent
                    evt.newIndex;  // element's new index within new parent
                    evt.oldDraggableIndex; // element's old index within old parent, only counting draggable elements
                    evt.newDraggableIndex; // element's new index within new parent, only counting draggable elements
                    evt.clone // the clone element
                    evt.pullMode;  // when item is in another sortable: `"clone"` if cloning, `true` if moving

                    //TODO: make MultiSection Working -> WIP
                    /*
                    console.log(evt);
                    //Need to rewrite all the Index Variables before starting _submitCard
                    let $parent = $(evt.item).parent();
                    //let $allCards = $($parent).children().not('.sortable-ignore');
                    let $allCards = $('#teamSiteAdmin').find('div:not(.sortable-ignore) div.team-card');
                    //let $card = $(evt.item).children('.team-card');
                    console.log($allCards);
                    let sectionIndexes = [];
                    let newArray = [];
                    $.each( self.teamSite, function( key, value ) {
                        $.each( value, function( subkey, subvalue ) {
                        //newArray[key] =  $.extend( true, {}, value);
                    });
                    //Working with all Sections
                    $allCards.each(function (index) {
                        let $section = $(this).closest('.team-section');
                        let newSection = $section.data('index');
                        console.log(this);
                        let $this = $(this);
                        let currentSection = $this.data('index').split("_")[0];
                        let oldRow = $this.data('index').split("_")[1];

                        if(!(newSection in sectionIndexes)) {
                            sectionIndexes[newSection] = 0;
                        }
                        let newIndex = newSection + "_" + sectionIndexes[newSection];

                        $this.data('index', newIndex);
                        $this.attr('data-index', newIndex);
                        $this.attr('id', 'team-card-' + newIndex);

                        newArray[newSection].entries[sectionIndexes[newSection]] = $.extend( true, {}, self.teamSite[currentSection].entries[oldRow]);

                        sectionIndexes[newSection]++;

                    });
                     */

                    //Need to rewrite all the Index Variables before starting _submitCard
                    let $parent = $(evt.item).parent();
                    let $allCards = $($parent).children().not('.sortable-ignore');
                    let i = 0;
                    let newArray = [];
                    $.each( self.teamSite, function( key, value ) {
                        newArray[key] =  $.extend( true, {}, value);
                    });
                    //Working with only one Section per Time
                    $allCards.each(function (index) {
                        let $teamCard = $(this).children();
                        let $entry = $(this).children().first();
                        let currentSection = $entry.data('index').split("_")[0];
                        let oldRow = $entry.data('index').split("_")[1];
                        let newIndex = currentSection + "_" + i;

                        $teamCard.data('index', newIndex);
                        $teamCard.attr('data-index', newIndex);
                        $teamCard.attr('id', 'team-card-' + newIndex);

                        newArray[currentSection].entries[i] = $.extend( true, {}, self.teamSite[currentSection].entries[oldRow]);

                        i++;

                    });
                    self.teamSite = newArray;
                    self.refresh();
                },
            });
        });
    }
});

let showAreYouSureFunction = function (e) {
    var confirmationMessage = "You have unsaved things!";

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

    $("#teamsite_edit_form").on("submit", function (_) {
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

    $(".team-section-add").on("click", function (e) {
        e.preventDefault();
        teamSiteAdmin.appendSection();
    });

    $("#addTeamMemberModal form").on("submit", function (e) {
        e.preventDefault();

        let index = $(this).find('input[name="index"]').val();
        let selectedUserData = $(this).find('select.select2-enable').select2('data');
        let user = selectedUserData[0].user

        if (user) {
            teamSiteAdmin.addTeamMember(index, user);
        }

        $("#addTeamMemberModal").modal('hide');
    });

    teamSiteAdmin.createSortable();
});