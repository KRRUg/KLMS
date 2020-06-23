(function ($, window, document, undefined) {
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
                    }).catch(() => {
                console.error('Error loading remote content!');
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
            let modalWrapper = document.querySelector('div#'+ MODULE_NAME);
                        
            if(!modalWrapper) {
                let elem = document.createElement("DIV");
                elem.setAttribute("id", MODULE_NAME);
                document.body.appendChild(elem);
                
                modalWrapper = document.querySelector('div#'+ MODULE_NAME);
            } 
            
            let $modalWrapper = $( modalWrapper );
            $modalWrapper.html(data);
            
            let $modal = $modalWrapper.children( ".modal" );
            return $modal;
        }
    });
    let MODULE_NAME = 'ajaxModal';
    let DATA_KEY = 'custom.' + MODULE_NAME;
    let EVENT_KEY = "." + DATA_KEY;
    let EVENT_CLICK_DATA_API = "click" + EVENT_KEY;
    let SELECTOR_DATA_TOGGLE = '[data-toggle="ajaxModal"]';
    
    $(document).on(EVENT_CLICK_DATA_API, SELECTOR_DATA_TOGGLE, function (event) {
        let remoteTarget = this.getAttribute('href');
        
        if(!remoteTarget) {
            return;
        }
        
        event.preventDefault();
        let am = new AjaxModal(remoteTarget);
        
    });

})(jQuery, window, document);