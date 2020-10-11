(function ($, window, document, undefined) {
    "use strict";
    let ConfirmModal = function (remoteTarget) {
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
            this.$modal = this._initModal(data);
            this.$modal.modal('show');
            
            /*this._getRemoteContent()
                    .then((data) => {
                        
                    }).catch((e) => {
                console.error('Error loading remote content!', e);
            });*/
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
                let modalHtml = this._getModalHtml();
                document.body.appendChild(modalHtml);

                modalWrapper = document.querySelector('div#' + MODULE_NAME);
            } else {
                $(modalWrapper).children(".modal").modal('dispose');
                $(".modal-backdrop").remove();
            }

            let $modalWrapper = $(modalWrapper);
            $modalWrapper.html(data);

            let $modal = $modalWrapper.children(".modal");
            return $modal;
        },
        _getModalHtml() {
            let modalHtlm = '<div class="modal fade" id="confirmeModal" tabindex="-1" role="dialog" aria-labelledby="confirmeModalLabel" aria-hidden="true">';
            modalHtlm += '<div class="modal-dialog" role="document">';
            modalHtlm += ' <div class="modal-content">';
            modalHtlm += '   <div class="modal-header">';
            modalHtlm += '     <h5 class="modal-title" id="confirmeModalLabel">Modal title</h5>';
            modalHtlm += '     <button type="button" class="close" data-dismiss="modal" aria-label="Close">';
            modalHtlm += '       <span aria-hidden="true">&times;</span>';
            modalHtlm += '     </button>';
            modalHtlm += '</div>';
            modalHtlm += '<div class="modal-body">';
            modalHtlm += '</div>';
            modalHtlm += '<div class="modal-footer">';
            modalHtlm += '<button type="button" class="btn btn-secondary" data-dismiss="modal">Nein</button>';
            modalHtlm += '<button type="button" class="btn btn-primary">Ja</button>';
            modalHtlm += '</div>';
            modalHtlm += '</div>';
            modalHtlm += '</div>';
            modalHtlm += '</div>';
            return modalHtlm;
        }
    });
    let MODULE_NAME = 'confirmModal';
    let DATA_KEY = 'custom.' + MODULE_NAME;
    let EVENT_KEY = "." + DATA_KEY;
    let EVENT_CLICK_DATA_API = "click" + EVENT_KEY;
    let SELECTOR_DATA_TOGGLE = '[data-toggle="confirmModal"]';

    $(document).on(EVENT_CLICK_DATA_API, SELECTOR_DATA_TOGGLE, function (event) {
        let remoteTarget = this.getAttribute('href');

        if (!remoteTarget) {
            return;
        }

        event.preventDefault();
        let am = new confirmModal(remoteTarget);

    });

})(jQuery, window, document);