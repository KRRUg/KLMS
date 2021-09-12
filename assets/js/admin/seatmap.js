import $ from 'jquery';
import 'jquery-ui';
import 'jquery-ui/ui/widgets/draggable';

import 'bootstrap';


let editMode = false;

document.addEventListener('DOMContentLoaded', function () {

    $('.seat').tooltip();
    $('.seat').draggable({
        disabled: true,
        containment: '.seatmap-wrapper',
        snap: true,
        snapMode: "inner",
        start: function () {
            $('.seat').tooltip("disable");
        },
        stop: function (event, ui) {
            $('.seat').tooltip("enable");

            const id = ui.helper.data('id');
            const url = $('.seatmap').data('positionUrl');

            $.ajax({
                url: url,
                type: "POST",
                data: JSON.stringify({top: ui.position.top, left: ui.position.left, id: id}),
                contentType: "application/json",
            })


        },
    });

    $('#toggleEditmode').on('click', function () {
        let $this = $(this);
        if ($this.data('editMode')) {
            $('.seat').draggable("disable");
            $this.data('editMode', false);
            $this.text('Drag & Drop aktivieren');
        } else {
            $('.seat').draggable("enable");
            $this.data('editMode', true);
            $this.text('Drag & Drop deaktivieren');
        }
    })

    "use strict";
    let AjaxModal = function (remoteTarget) {
        this.remoteTarget = remoteTarget;
        /*this.$modal.on(
                'show.bs.modal',
                this.handleModalShow.bind(this)
                );
        this.$modal.on(
                'hide.bs.modal',
                this.handleModalHide.bind(this)
                );
        this.$modal.on(
                'submit',
                'form',
                this.handleFormSubmit.bind(this)
                );*/
        this.init();
    };
    $.extend(AjaxModal.prototype, {
        init() {
            this._getRemoteContent()
                .then((data) => {
                    this.$modal = this._initModal(data);
                    this.$modal.modal('show');
                    //this.$modal.find(".admin-data-table").AdminDataTable();
                }).catch((e) => {
                console.error('Error loading remote content!', e);
            });
        },
        _getRemoteContent() {
            return new Promise((resolve, reject) => {
                $.ajax({
                    method: 'GET',
                    url: this.remoteTarget,
                }).then((data, textStatus, jqXHR) => {
                    resolve(data);
                }).catch((jqXHR) => {
                    reject();
                });
            });
        },
        _initModal(data) {
            let modalWrapper = document.querySelector('div#' + MODULE_NAME);

            if (!modalWrapper) {
                let elem = document.createElement("DIV");
                elem.setAttribute("id", MODULE_NAME);
                document.body.appendChild(elem);

                modalWrapper = document.querySelector('div#' + MODULE_NAME);
            } else {
                $(modalWrapper).children(".modal").modal('dispose');
                $(".modal-backdrop").remove();
            }

            let $modalWrapper = $(modalWrapper);
            $modalWrapper.html(data);

            let $modal = $modalWrapper.children(".modal");
            return $modal;
        }
    });
    let MODULE_NAME = 'ajaxModal';
    let DATA_KEY = 'custom.' + MODULE_NAME;
    let EVENT_KEY = "." + DATA_KEY;
    //let EVENT_CLICK_DATA_API = "contextmenu" + EVENT_KEY;
    let EVENT_CLICK_DATA_API = "contextmenu";
    let SELECTOR_DATA_TOGGLE = '.seatmap-wrapper';

    $(SELECTOR_DATA_TOGGLE).on(EVENT_CLICK_DATA_API, function (event) {
        let remoteTarget = $('.seatmap').data('createUrl') + '?x=' + event.offsetX + '&y=' + event.offsetY;

        if (!remoteTarget) {
            return;
        }

        event.preventDefault();
        let am = new AjaxModal(remoteTarget);

    });

});