import { Controller } from "@hotwired/stimulus";
import { Popover } from 'bootstrap';

export default class extends Controller {
    connect() {
        this.element
            .querySelectorAll('.team:not(.team-empty)')
            .forEach((team) => {
                const teamInfo = team.querySelector('.team-info');
                new Popover(team, {
                    html: true,
                    container: 'body',
                    trigger: 'hover',
                    placement: team.id.endsWith('a') ? 'top' : 'bottom',
                    title: () => teamInfo.querySelector('.team-info-head').innerHTML,
                    content: () => teamInfo.querySelector('.team-info-body').innerHTML,
                });
                teamInfo.remove();
            });
    }
}
