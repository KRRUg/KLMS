import $ from 'jquery';

const AjaxLoader = function($root, config) {
    this.$root = $root;

    this.path = config['path'];
    this.load = config['load'] || 4;
    this.offset = config['offset'] || 'offset';
    this.total = config['total'] || 'total';

    this.$button = $(config['button']);
    this.$spinner = $(config['spinner']);
    this.$endLabel = $(config['end_label']);

    if (this.$button) {
        this.$button.on('click', this.loadMore.bind(this));
    }
};

$.extend(AjaxLoader.prototype, {
    loadMore() {
        let offset = this.$root.data(this.offset);
        let total = this.$root.data(this.total);

        if (offset >= total) {
            this._end();
            return;
        }

        // 1. show spinner
        this._start();

        // 2. start request
        let load = Math.min(this.load, total - offset);
        this._requestData(offset, load)
            .then((data) => {
                let o = offset + load;
                this.$root.append(data);
                this.$root.data(this.offset, o);
                this._updateUrl(o);
                if (o >= total)
                    this._end();
                else
                    this._reset();
            }).catch(() => {
                console.error('failed to load more data via ajax');
                this._end();
            });
    },

    _updateUrl(cnt) {
        let pageUrl = '?' + 'cnt=' + cnt.toString();
        history.replaceState(null, '', pageUrl);
    },

    _requestData(offset, count) {
        return new Promise(((resolve, reject) => {
            $.ajax({
                url: this.path,
                method: 'GET',
                dataType: "html",
                data: {
                    'offset': offset,
                    'count' : count,
                },
            }).then((data, textStatus, jqXHR) => {
                resolve(data);
            }).catch((jqXHR) => {
                reject();
            });
        }));
    },

    _start() {
        this.$button.hide();
        if (this.$spinner) this.$spinner.show();
        if (this.$endLabel) this.$endLabel.hide();
    },

    _end() {
        this.$button.hide();
        if (this.$spinner) this.$spinner.hide();
        if (this.$endLabel) this.$endLabel.show();
    },

    _reset() {
        this.$button.show();
        if (this.$spinner) this.$spinner.hide();
        if (this.$endLabel) this.$endLabel.hide();
    }
});

$(document).ready(() => {
    const al = new AjaxLoader($('#news'), {
        path: '/news/cards',
        button: '#loadMore',
        end_label: '#noMore',
        spinner: '#spinner',
    });
});