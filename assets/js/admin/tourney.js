document.addEventListener('DOMContentLoaded', function () {
	const form = document.getElementById('tourney-team-form');
	if (!form) {
		return;
	}

	const modal = form.closest('.modal');
	const submitButton = document.querySelector('button[form="tourney-team-form"][type="submit"]');
	if (submitButton && !submitButton.dataset.originalText) {
		submitButton.dataset.originalText = submitButton.innerHTML;
	}

	const membersContainer = document.getElementById('members-container');
	const addMemberBtn = document.getElementById('add-member');
	let memberCount = membersContainer ? membersContainer.querySelectorAll('.member-item').length : 0;
	const prototype = membersContainer ? membersContainer.dataset.prototype : null;
	const maxSizeAttr = membersContainer ? membersContainer.dataset.maxSize : '';
	const maxTeamSize = maxSizeAttr ? parseInt(maxSizeAttr, 10) || 0 : 0;

	if (addMemberBtn && membersContainer && prototype) {
		addMemberBtn.addEventListener('click', function (event) {
			event.preventDefault();

			if (maxTeamSize > 0 && getMemberItems().length >= maxTeamSize) {
				window.alert('Maximale Teamgröße erreicht (' + maxTeamSize + ' Spieler).');
				return;
			}

			const index = memberCount;
			const html = prototype.replace(/__name__/g, String(index));
			const wrapper = document.createElement('div');
			wrapper.innerHTML = html;
			const newItem = wrapper.firstElementChild;
			if (!newItem) {
				return;
			}

			membersContainer.appendChild(newItem);
			memberCount += 1;
			wireRemoveButtons(newItem.querySelectorAll('.remove-member'));
			enhanceSelects(newItem.querySelectorAll('select[data-user-select]'));
		});
	}

	wireRemoveButtons(document.querySelectorAll('.remove-member'));
	enhanceSelects(document.querySelectorAll('select[data-user-select]'));

	form.addEventListener('submit', function () {
		if (submitButton) {
			submitButton.disabled = true;
			submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Speichere...';
		}
	});

	const tourneyType = form.dataset.tourneyType;
	const teamNameInput = document.getElementById('tourney_team_name');
	if (tourneyType === 'single' && teamNameInput) {
		const nameGroup = teamNameInput.closest('.form-group');
		if (nameGroup) {
			nameGroup.style.display = 'none';
		}

		const singleSelect = document.getElementById('tourney_team_singlePlayer');
		if (singleSelect) {
			singleSelect.addEventListener('change', function () {
				const option = this.selectedOptions[0];
				if (option) {
					teamNameInput.value = option.text;
				}
			});
		}
	}

	if (modal) {
		modal.addEventListener('hidden.bs.modal', function () {
			form.reset();
			if (submitButton) {
				submitButton.disabled = false;
				submitButton.innerHTML = submitButton.dataset.originalText || 'Speichern';
			}
		});
	}

	function getMemberItems() {
		return membersContainer ? Array.from(membersContainer.querySelectorAll('.member-item')) : [];
	}

	function wireRemoveButtons(buttons) {
		buttons.forEach(function (btn) {
			btn.addEventListener('click', function () {
				const item = btn.closest('.member-item');
				if (!item) {
					return;
				}
				const items = getMemberItems();
				if (items.length <= 1) {
					window.alert('Mindestens ein Spieler muss im Team sein.');
					return;
				}
				item.remove();
			}, { once: true });
		});
	}

	function enhanceSelects(selects) {
		selects.forEach(function (select) {
			if (window.jQuery && window.jQuery.fn.select2 && !select.dataset.select2) {
				window.jQuery(select).select2({
					theme: 'bootstrap4',
					placeholder: 'Spieler auswählen...',
					allowClear: true
				});
			}
		});
	}
});
