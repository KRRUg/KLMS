import $ from "jquery";

;
(function ($, window, document, undefined) {
    "use strict";
    let ConfirmModal = function (remoteTarget, options) {
        this.remoteTarget = remoteTarget;
        
        let defaults = {
            title: "Löschen bestätigen",
            text: "Sind Sie sicher, dass Sie dieses Element löschen wollen?"
        };

        this.settings = $.extend({}, defaults, options);
        this._defaults = defaults;
        
        this.init();
        this.$modal.on(
                'click',
                '.js-confirm',
                this._handleConfirmAction.bind(this)
                );
    };
    $.extend(ConfirmModal.prototype, {
        init() {
            this.$modal = this._initModal();
            this.$modal.modal('show');
        },
        _initModal() {
            let modalWrapper = document.querySelector('div#' + MODULE_NAME);

            if (!modalWrapper) {
                let elem = document.createElement("DIV");
                elem.setAttribute("id", MODULE_NAME);
                elem.innerHTML = this._getModalHtml();

                document.body.appendChild(elem);

                modalWrapper = document.querySelector('div#' + MODULE_NAME);
            } else {
                $(modalWrapper).children(".modal").modal('dispose');
                $(".modal-backdrop").remove();
            }

            let $modalWrapper = $(modalWrapper);
            $modalWrapper.html(this._getModalHtml());

            let $modal = $modalWrapper.children(".modal");
            return $modal;
        },
        _getModalHtml() {
            let modalHtml = '';
            modalHtml += '<div class="modal fade" id="confirmModal" tabindex="-1" role="dialog" aria-labelledby="confirmModalLabel" aria-hidden="true">';
            modalHtml += '<div class="modal-dialog" role="document">';
            modalHtml += ' <div class="modal-content">';
            modalHtml += '   <div class="modal-header">';
            modalHtml += '     <h5 class="modal-title" id="confirmModalLabel">'+this.settings.title+'</h5>';
            modalHtml += '</div>';
            modalHtml += '<div class="modal-body">';
            modalHtml += '<p>'+this.settings.text+'</p>';
            modalHtml += '</div>';
            modalHtml += '<div class="modal-footer">';
            modalHtml += '<button type="button" class="btn btn-secondary" data-dismiss="modal">Nein</button>';
            modalHtml += '<button type="button" class="btn btn-primary js-confirm">Ja</button>';
            modalHtml += '</div>';
            modalHtml += '</div>';
            modalHtml += '</div>';
            modalHtml += '</div>';
            return modalHtml;
        },
        _handleConfirmAction() {
            if($(this.remoteTarget).is('form')) {
                if (this.remoteTarget.reportValidity()) {
                    this.remoteTarget.submit();
                } else {
                    this.$modal.modal('hide');
                }
            } else if ($(this.remoteTarget).is('button') && this.remoteTarget.type === 'submit') {
                if (this.remoteTarget.form.reportValidity()) {
                    this.remoteTarget.removeAttribute('data-toggle');
                    this.remoteTarget.click();
                } else {
                    this.$modal.modal('hide');
                }
            } else if ($(this.remoteTarget).is('a')) {
                window.location.href = $(this.remoteTarget).attr("href");
            } else {
                console.error("remoteTarget type not supported!");
            }
        }
    });
    let MODULE_NAME = 'confirmModal';
    let DATA_KEY = 'custom.' + MODULE_NAME;
    let EVENT_KEY = "." + DATA_KEY;
    let EVENT_CLICK_DATA_API = "click" + EVENT_KEY;
    let SELECTOR_DATA_TOGGLE = '[data-toggle="confirmModal"]';

    $(document).on(EVENT_CLICK_DATA_API, SELECTOR_DATA_TOGGLE, function (event) {
        event.preventDefault();
        let $currentTarget = event.currentTarget;
        let options = {
            title: $(this).data('modal-title'),
            text: $(this).data('modal-text')
        };

        let cm = new ConfirmModal($currentTarget, options);
    });

})(jQuery, window, document);