import $ from 'jquery';

import '../modules/adminDataTable/jquery.adminDataTable.js';

const initAdminTables = () => {
    const tables = $('.admin-data-table');
    if (tables.length && typeof tables.AdminDataTable === 'function') {
        tables.AdminDataTable();
    }
};

const initSeedDragAndDrop = () => {
    const seedList = document.getElementById('seedList');
    if (!seedList || seedList.dataset.dragInitialized === 'true') {
        return;
    }
    seedList.dataset.dragInitialized = 'true';

    const updateBadges = () => {
        seedList.querySelectorAll('.list-group-item').forEach((item, index) => {
            const badge = item.querySelector('.badge');
            if (badge) {
                badge.textContent = index + 1;
            }
        });
    };

    let draggedItem = null;

    seedList.querySelectorAll('.list-group-item').forEach(item => {
        item.draggable = true;

        item.addEventListener('dragstart', event => {
            draggedItem = item;
            item.classList.add('dragging');
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/html', item.innerHTML);
        });

        item.addEventListener('dragend', () => {
            item.classList.remove('dragging');
            draggedItem = null;
            updateBadges();
        });
    });

    seedList.addEventListener('dragover', event => {
        if (!draggedItem) {
            return;
        }

        const target = event.target.closest('.list-group-item');
        event.preventDefault();

        if (!target) {
            seedList.appendChild(draggedItem);
            updateBadges();
            return;
        }
        if (target === draggedItem) {
            return;
        }

        const { top, height } = target.getBoundingClientRect();
        const insertAfter = event.clientY > top + height / 2;
        seedList.insertBefore(draggedItem, insertAfter ? target.nextSibling : target);
        updateBadges();
    });

    seedList.addEventListener('drop', event => {
        event.preventDefault();
        draggedItem = null;
        updateBadges();
    });
};

const init = () => {
    initAdminTables();
    initSeedDragAndDrop();
};

document.readyState === 'loading'
    ? document.addEventListener('DOMContentLoaded', init)
    : init();

// Lausche auf AJAX-Modal das dynamisch geladen wird
$(document).on('shown.bs.modal', '.modal', function() {
    // Prüfe ob das Seed-Modal geöffnet wurde
    if ($(this).find('#seedList').length > 0) {
        // Reset das Flag
        const seedList = document.getElementById('seedList');
        if (seedList) {
            delete seedList.dataset.dragInitialized;
        }
        setTimeout(initSeedDragAndDrop, 100);
    }
});
