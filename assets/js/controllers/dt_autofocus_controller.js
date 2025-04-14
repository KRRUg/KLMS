import {Controller} from '@hotwired/stimulus';
import $ from 'jquery';

/*
* The following line makes this controller "lazy": it won't be downloaded until needed
* See https://github.com/symfony/stimulus-bridge#lazy-controllers
*/
/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = []

    // Add the controller to the Container around the DataTable to autofocus the search input
    connect() {
        const $table = $(this.element).find('table');
        const dom = this.element
        $($table).on('draw.dt', function () {
            const $searchElement = $(dom).find('div.dataTables_filter input[type=search]');
            $searchElement.focus();
        });
    }
}