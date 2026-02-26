class PollWidget {
    constructor(root) {
        this.root = root;
        this.fetchUrl = root.dataset.fetchUrl || '';
        this.voteUrl = root.dataset.voteUrl || '';
        this.csrfTokenUrl = root.dataset.csrfTokenUrl || '';
        this.element = document.createElement('div');
        this.element.className = 'poll-widget is-hidden';
        root.appendChild(this.element);
        this.state = null;
        this.csrfToken = null;
        this.isVoting = false;
        this.dismissStorageKey = 'pollWidgetDismissed';
        this.load();
    }

    async load() {
        if (!this.fetchUrl) {
            return;
        }

        try {
            // Lade CSRF-Token und Poll-Daten parallel
            const [pollResponse, tokenResponse] = await Promise.all([
                fetch(this.fetchUrl, { headers: { Accept: 'application/json' } }),
                this.csrfTokenUrl ? fetch(this.csrfTokenUrl, { headers: { Accept: 'application/json' } }) : Promise.resolve(null)
            ]);

            if (!pollResponse.ok) {
                return;
            }
            const data = await pollResponse.json();
            if (!data) {
                return;
            }

            // Token laden, falls verfügbar
            if (tokenResponse && tokenResponse.ok) {
                const tokenData = await tokenResponse.json();
                this.csrfToken = tokenData?.token || null;
            }

            this.state = data;
            this.render();
        } catch (error) {
            // the widget should fail silently if the API is not available
        }
    }

    render() {
        const { poll, hasVoted, isClosed, results, isAuthenticated } = this.state || {};
        
        if (!poll || this.isDismissed(poll.id)) {
            this.hide();
            return;
        }
        
        const canVote = !poll.onlyRegistered || Boolean(isAuthenticated);

        const closeButton = `
            <button type="button" class="poll-widget-close" aria-label="Umfrage schließen">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                    <path d="M12 4L4 12M4 4l8 8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
            </button>
        `;

        const header = `
            <header>
                <div>Community Umfrage</div>
            </header>
        `;

        let body = '<div class="poll-body">';
        body += `<div class="poll-question">${this.escape(poll.question)}</div>`;

        if (hasVoted || isClosed) {
            body += this.renderResults(results);
        } else {
            body += this.renderOptions(poll, canVote);
        }

        const metaParts = [];
        if (poll.onlyRegistered) {
            metaParts.push('Nur angemeldete Nutzer können abstimmen');
        }
        if (results && typeof results.total === 'number') {
            metaParts.push(`${results.total} Stimme(n) gesamt`);
        }

        if (metaParts.length) {
            body += `<div class="poll-meta">${metaParts.join('<br/>')}</div>`;
        }

        body += '</div>';

        this.element.innerHTML = closeButton + header + body;
        this.element.classList.remove('is-hidden');
        this.registerCloseHandler();
        if (!hasVoted && !isClosed && canVote) {
            this.registerOptionHandlers();
        }
    }

    renderOptions(poll, canVote) {
        if (!Array.isArray(poll.options)) {
            return '';
        }

        const options = poll.options.map(option => {
            const attrs = canVote ? '' : ' disabled class="is-disabled"';
            return `<button type="button"${attrs} data-option-id="${option.id}">${this.escape(option.label)}</button>`;
        }).join('');

        return `<div class="poll-options">${options}</div>`;
    }

    renderResults(results) {
        if (!results || !Array.isArray(results.options)) {
            return '<div class="poll-results">Keine Stimmen vorhanden.</div>';
        }

        // Finde die Option(en) mit den meisten Stimmen
        const maxVotes = Math.max(...results.options.map(opt => opt.votes || 0));

        const rows = results.options.map(option => {
            const width = Math.min(100, Math.max(0, option.percentage || 0));
            const percentageLabel = this.formatPercentage(option.percentage);
            const classes = ['poll-result-row'];
            // Markiere die Option mit den meisten Stimmen
            if ((option.votes || 0) === maxVotes && maxVotes > 0) {
                classes.push('is-selected');
            }
            return `
                <div class="${classes.join(' ')}">
                    <strong>
                        <span>${this.escape(option.label)}</span>
                        <span>${option.votes} (${percentageLabel}%)</span>
                    </strong>
                    <div class="poll-result-bar"><span style="width: ${width}%"></span></div>
                </div>
            `;
        }).join('');

        return `<div class="poll-results">${rows}</div>`;
    }

    registerOptionHandlers() {
        const buttons = this.element.querySelectorAll('button[data-option-id]');
        buttons.forEach(button => {
            button.addEventListener('click', () => this.vote(parseInt(button.dataset.optionId, 10)));
        });
    }

    registerCloseHandler() {
        const closeButton = this.element.querySelector('.poll-widget-close');
        closeButton?.addEventListener('click', () => this.dismiss());
    }

    async vote(optionId) {
        if (this.isVoting || !this.voteUrl || !this.state || !this.state.poll) {
            return;
        }

        // CSRF-Token laden falls noch nicht vorhanden
        if (!this.csrfToken && this.csrfTokenUrl) {
            try {
                const tokenResponse = await fetch(this.csrfTokenUrl, { headers: { Accept: 'application/json' } });
                if (tokenResponse.ok) {
                    const tokenData = await tokenResponse.json();
                    this.csrfToken = tokenData?.token || null;
                }
            } catch (error) {
                // Token-Fehler ignorieren, Server wird mit 403 antworten
            }
        }

        this.isVoting = true;
        try {
            const response = await fetch(this.voteUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    pollId: this.state.poll.id,
                    optionId,
                    csrfToken: this.csrfToken || ''
                })
            });

            if (!response.ok) {
                if (response.status === 403) {
                    alert('Bitte melde dich an, um abzustimmen.');
                } else if (response.status === 409) {
                    alert('Du hast bereits abgestimmt.');
                } else if (response.status === 429) {
                    alert('Zu viele Abstimmungsversuche. Bitte versuche es später erneut.');
                }
                return;
            }

            const data = await response.json();
            if (data) {
                this.state = data;
                this.render();
            }
        } catch (error) {
            alert('Die Stimme konnte nicht gespeichert werden. Bitte versuche es später erneut.');
        } finally {
            this.isVoting = false;
        }
    }

    formatPercentage(value) {
        return new Intl.NumberFormat('de-DE', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 1
        }).format(Number.isFinite(value) ? value : 0);
    }

    escape(text) {
        return String(text ?? '').replace(/[&<>'"]/g, match => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#39;'
        })[match]);
    }

    dismiss() {
        this.storeDismissedPollId(this.state?.poll?.id);
        this.hide();
    }

    hide() {
        this.element.classList.add('is-hidden');
        this.element.innerHTML = '';
    }

    isDismissed(pollId) {
        return pollId && this.getDismissedPollIds().includes(pollId);
    }

    storeDismissedPollId(pollId) {
        if (!pollId) return;

        const dismissed = this.getDismissedPollIds();
        if (dismissed.includes(pollId)) return;

        dismissed.push(pollId);
        try {
            sessionStorage.setItem(this.dismissStorageKey, JSON.stringify(dismissed));
        } catch {
            // ignore storage issues, dismissal is non-critical
        }
    }

    getDismissedPollIds() {
        try {
            const raw = sessionStorage.getItem(this.dismissStorageKey);
            if (!raw) {
                return [];
            }

            const parsed = JSON.parse(raw);
            return Array.isArray(parsed) ? parsed : [];
        } catch (error) {
            return [];
        }
    }
}

function bootstrapPollWidget() {
    const root = document.getElementById('poll-widget-root');
    if (root) new PollWidget(root);
}

document.addEventListener('DOMContentLoaded', bootstrapPollWidget);
