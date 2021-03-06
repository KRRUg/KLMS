(function ($, window, document, undefined) {
    "use strict";
    let ConfirmModal = function (remoteTarget) {
        this.remoteTarget = remoteTarget;
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
        _initModal(data) {
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
            let modalHtlm = '<div class="modal fade" id="confirmeModal" tabindex="-1" role="dialog" aria-labelledby="confirmeModalLabel" aria-hidden="true">';
            modalHtlm += '<div class="modal-dialog" role="document">';
            modalHtlm += ' <div class="modal-content">';
            modalHtlm += '   <div class="modal-header">';
            modalHtlm += '     <h5 class="modal-title" id="confirmeModalLabel">Löschen bestätigen</h5>';
            modalHtlm += '</div>';
            modalHtlm += '<div class="modal-body">';
            modalHtlm += '<p>Sind Sie sicher, dass Sie dieses Element löschen wollen?</p>';
            modalHtlm += '</div>';
            modalHtlm += '<div class="modal-footer">';
            modalHtlm += '<button type="button" class="btn btn-secondary" data-dismiss="modal">Nein</button>';
            modalHtlm += '<button type="button" class="btn btn-primary js-confirm">Ja</button>';
            modalHtlm += '</div>';
            modalHtlm += '</div>';
            modalHtlm += '</div>';
            modalHtlm += '</div>';
            return modalHtlm;
        },
        _handleConfirmAction() {
            if($(this.remoteTarget).is('form')) {
                this.remoteTarget.submit();
            } else if ($(this.remoteTarget).is('a')) {
                let href = $(this.remoteTarget).attr("href");
                window.location.href = href;
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

        let cm = new ConfirmModal($currentTarget);
    });

})(jQuery, window, document);