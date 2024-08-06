import $ from 'jquery';

const Shop = function ($root, config) {
    this.$root = $root;

    this.$tickets = this.$root.find('#ticketWrapper');
    this.$buttonsWrapper = this.$tickets.find('#chooseTicketWrapper');
    this.$buttons = this.$buttonsWrapper.find('[data-mode]');
    this.$buttons.on('click', (e) => this.smNext(e.currentTarget.dataset['mode']));

    this.$paneOne = this.$tickets.find('#ticketSelfBody');
    this.$paneMore = this.$tickets.find('#ticketClanBody');
    this.$paneMore.find('#button-confirm-ticket').on('click', () => this.smNext());
    this.$paneRedeem = this.$tickets.find('#redeemCodeBody');

    this.$form = this.$root.find('form');
    this.$formTicketCount = this.$paneMore.find('input');
    this.$formRedeemInput = this.$paneRedeem.find('input');
    this.$formRedeemButton = this.$paneRedeem.find('button');
    this.$formRedeemButton.on('click', () => this._redeem());

    this.$addons = this.$root.find('#addonWrapper');
    this.$addonInputs = this.$addons.find('input');
    this.$buttonReset = this.$root.find('#buttonReset');
    this.$buttonReset.on('click', () => this.smClear());

    this.$submit = this.$root.find('#submitWrapper');

    this.path = config['path'];
    this.smClear();
}

const States = Object.freeze({
    START:   Symbol("start"),
    REDEEM:  Symbol("redeem"),
    BUY:  Symbol("buy"),
});

$.extend(Shop.prototype, {
    smClear() {
        this.state = States.START;
        this.$buttonsWrapper.removeClass('d-none');
        this.$paneRedeem.addClass('d-none');
        this.$paneOne.addClass('d-none');
        this.$paneMore.addClass('d-none');
        this.$addons.addClass('d-none');
        this.$submit.addClass('d-none')
        this._redeemButtonState();
    },
    smNext(mode) {
        let new_state = this.state;
        switch (this.state) {
            case States.START:
                switch (mode) {
                    case 'one':
                    case 'multi':
                        new_state = States.BUY;
                        break;
                    case 'redeem':
                        new_state = States.REDEEM;
                        break;
                }
                break;
            case States.REDEEM:
                new_state = States.BUY;
                break;
            case States.BUY:
                break;
        }
        if (new_state !== this.state) {
            this.updateUi(mode);
            this.state = new_state;
        }
    },
    updateUi(mode) {
        switch (this.state) {
            case States.START:
                switch (mode) {
                    case 'one':
                        this._showSingle();
                        this._showAddon();
                        break;
                    case 'multi':
                        this._showMany();
                        this._showAddon();
                        break;
                    case 'redeem':
                        this._showRedeem();
                        break;
                }
                break;
            case States.REDEEM:
                this._showAddon();
                break;
            case States.BUY:
                break;
        }
    },
    _showSingle() {
        this.$formTicketCount.val(1);
        this.$buttonsWrapper.addClass('d-none');
        this.$paneOne.removeClass('d-none');
    },
    _showMany() {
        this.$formTicketCount.val(1);
        //this.$formTicketCount.min(1);
        this.$buttonsWrapper.addClass('d-none');
        this.$paneMore.removeClass('d-none');
    },
    _showRedeem() {
        this.$formTicketCount.val(0);
        this.$formRedeemInput.val('');
        this.$buttonsWrapper.addClass('d-none');
        this.$paneRedeem.removeClass('d-none');
    },
    _showAddon() {
        this.$addons.find('input').val(0);
        this.$addons.removeClass('d-none');
        this.$submit.removeClass('d-none');
    },
    _checkCode(code) {
        return new Promise(((resolve, reject) => {
            $.ajax({
                url: this.path,
                method: 'GET',
                data: {
                    'code': code,
                },
            }).then((data, textStatus, jqXHR) => {
                if (data.result === true) { resolve(data); }
                else { reject(); }
            }).catch((jqXHR) => {
                reject();
            });
        }));
    },
    _redeem() {
        const elem = this.$formRedeemInput;
        const code = elem.val()
        const pattern = elem.attr("pattern");
        const re = new RegExp(pattern);
        if (re.test(code)) {
            this._checkCode(code)
                .then(r => { this._redeemButtonState(true); this.smNext();})
                .catch(r => { this._redeemButtonState(false);});
        } else {
            this._redeemButtonState(false);
        }
    },
    _redeemButtonState(ok) {
        if (ok === true) {
            this.$formRedeemButton.prop('disabled', true);
            this.$formRedeemInput.prop('readonly', true).removeClass('is-invalid').addClass('is-valid');
        } else if (ok === false) {
            this.$formRedeemInput.prop('readonly', false).removeClass('is-valid').addClass('is-invalid');
        } else {
            this.$formRedeemButton.prop('disabled', false);
            this.$formRedeemInput.prop('readonly', false).removeClass('is-invalid').removeClass('is-valid');
        }
    },
});

$(document).ready(() => {
    const shop = new Shop($('#shop'),{
        path: '/shop/check',
    });
});