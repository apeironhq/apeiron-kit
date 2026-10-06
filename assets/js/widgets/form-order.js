(function () {
	'use strict';
	var initialized = new WeakMap();

	function init(root) {
		var form = root.querySelector('form');
		if (!form || initialized.get(root) === form) { return; }
		var steps = Array.prototype.slice.call(form.querySelectorAll('[data-order-step]'));
		var back = form.querySelector('.apeiron-form-order__button--back');
		var next = form.querySelector('.apeiron-form-order__button--next');
		var submit = form.querySelector('.apeiron-form-order__button--submit');
		var notice = form.querySelector('.apeiron-form-order__notice');
		var progress = form.querySelector('.apeiron-form-order__progress');
		var config;
		try { config = JSON.parse(root.getAttribute('data-apeiron-form-order') || '{}'); } catch (error) { return; }
		var current = 0;
		var busy = false;
		var completed = null;
		var submissionId = '';
		var submissionFields = '';
		var draftSettings = config.draft || {};
		var draftStorage = null;
		var draftKey = '';
		var draftTimer = null;
		var draftDirty = false;
		var restoringDraft = true;
		var draftMessage = form.querySelector('[data-order-draft-message]');
		var draftClear = root.querySelector('[data-order-draft-clear]');
		var draftPanel = form.querySelector('.apeiron-form-order__draft');
		var draftDays = [1, 3, 7, 14, 30].indexOf(Number(draftSettings.days)) === -1 ? 7 : Number(draftSettings.days);
		initialized.set(root, form);
		form.querySelectorAll('[data-order-optional]').forEach(function (group) {
			group.querySelectorAll('input, select, textarea').forEach(function (input) { input.disabled = group.hidden; });
		});
		form.addEventListener('click', function (event) {
			var toggle = event.target.closest('[data-order-toggle-group]');
			if (toggle && form.contains(toggle)) {
				var group = document.getElementById(toggle.getAttribute('data-order-toggle-group'));
				if (!group || !form.contains(group)) { return; }
				group.hidden = !group.hidden;
				group.querySelectorAll('input, select, textarea').forEach(function (input) { input.disabled = group.hidden; });
				toggle.setAttribute('aria-expanded', group.hidden ? 'false' : 'true');
				toggle.hidden = !group.hidden;
				if (!group.hidden) { var first = group.querySelector('input, textarea, select'); if (first) { first.focus(); } }
			}
			var close = event.target.closest('[data-order-close-group]');
			if (close && form.contains(close)) {
				var optional = close.closest('[data-order-optional]');
				optional.hidden = true;
				optional.querySelectorAll('input, select, textarea').forEach(function (input) { input.disabled = true; });
				var trigger = form.querySelector('[data-order-toggle-group="' + optional.id + '"]');
				if (trigger) { trigger.hidden = false; trigger.setAttribute('aria-expanded', 'false'); trigger.focus(); }
			}
			var add = event.target.closest('[data-order-add-account]');
			if (add && form.contains(add)) {
				var bank = add.closest('[data-order-bank-group]');
				var original = bank.querySelector('[name="bank_account_2"]');
				if (!original) { return; }
				var number = 3;
				while (number <= 10 && form.querySelector('[name="bank_account_' + number + '"]')) { number++; }
				if (number > 10) { return; }
				var title = 'Rekening ' + number;
				var account = document.createElement('div');
				account.className = 'apeiron-form-order__extra-account';
				account.setAttribute('data-order-account-row', '');
				var heading = document.createElement('h4');
				heading.className = 'apeiron-form-order__heading';
				heading.textContent = title;
				account.appendChild(heading);
				var grid = document.createElement('div');
				grid.className = 'apeiron-form-order__grid';
				[ ['name', 'Nama Bank'], ['account', 'Nomor Rekening'], ['owner', 'Nama Pemilik'] ].forEach(function (part) {
					var source = bank.querySelector('[name="bank_' + part[0] + '_2"]');
					if (!source) { return; }
					var field = source.closest('.apeiron-form-order__field').cloneNode(true);
					var input = field.querySelector('input');
					var label = field.querySelector('label');
					input.name = 'bank_' + part[0] + '_' + number;
					input.id = source.id + '-extra-' + number;
					input.value = ''; input.required = false; input.removeAttribute('aria-invalid');
					label.htmlFor = input.id; label.textContent = part[1] + ' ' + number;
					field.setAttribute('data-order-label', label.textContent);
					grid.appendChild(field);
				});
				account.appendChild(grid);
				var remove = document.createElement('button');
				remove.type = 'button';
				remove.className = 'apeiron-form-order__remove-account';
				remove.setAttribute('data-order-remove-account', '');
				remove.textContent = '\u00d7';
				remove.setAttribute('aria-label', 'Hapus ' + title);
				account.appendChild(remove);
				bank.insertBefore(account, add);
				grid.querySelector('input').focus();
			}
			var removeButton = event.target.closest('[data-order-remove-account]');
			if (removeButton && form.contains(removeButton)) {
				var container = removeButton.closest('[data-order-bank-group]');
				removeButton.closest('[data-order-account-row]').remove();
				container.querySelector('[data-order-add-account]').focus();
			}
			var addJourney = event.target.closest('[data-order-add-journey]');
			if (addJourney && form.contains(addJourney)) {
				var storyStep = addJourney.closest('[data-order-story]');
				var journeyNumber = 1;
				while (journeyNumber <= 10 && storyStep.querySelector('[name="love_extra_' + journeyNumber + '_title"]')) { journeyNumber++; }
				if (journeyNumber > 10) { return; }
				var journeyTitle = 'Perjalanan ' + (journeyNumber + 3);
				var journey = document.createElement('div');
				journey.className = 'apeiron-form-order__group apeiron-form-order__story-group';
				journey.setAttribute('data-order-story-group', '');
				journey.setAttribute('data-order-story-extra', '');
				journey.setAttribute('data-order-story-default-title', journeyTitle);
				var journeyHeading = document.createElement('h4');
				journeyHeading.className = 'apeiron-form-order__heading';
				journeyHeading.textContent = journeyTitle;
				journey.appendChild(journeyHeading);
				var removeJourney = document.createElement('button');
				removeJourney.type = 'button';
				removeJourney.className = 'apeiron-form-order__close-group';
				removeJourney.setAttribute('data-order-remove-journey', '');
				removeJourney.setAttribute('aria-label', 'Hapus ' + journeyTitle);
				removeJourney.textContent = '\u00d7';
				journey.appendChild(removeJourney);
				var journeyGrid = document.createElement('div');
				journeyGrid.className = 'apeiron-form-order__grid';
				[
					['title', 'Judul Perjalanan', 'text', '50', journeyTitle],
					['date', 'Tanggal Perjalanan', 'date', '50', ''],
					['story', 'Cerita Perjalanan', 'textarea', '100', '']
				].forEach(function (definition) {
					var field = document.createElement('div');
					field.className = 'apeiron-form-order__field';
					field.setAttribute('data-order-label', definition[1]);
					field.style.setProperty('--order-field-width', definition[3] + '%');
					field.style.setProperty('--order-field-width-tablet', '100%');
					field.style.setProperty('--order-field-width-mobile', '100%');
					var label = document.createElement('label');
					label.className = 'apeiron-form-order__field-label';
					label.textContent = definition[1];
					var input = document.createElement(definition[2] === 'textarea' ? 'textarea' : 'input');
					var id = 'apeiron-order-' + (config.elementId || 'order') + '-love-extra-' + journeyNumber + '-' + definition[0];
					label.htmlFor = id;
					input.id = id;
					input.name = 'love_extra_' + journeyNumber + '_' + definition[0];
					input.className = 'apeiron-form-order__input';
					input.maxLength = definition[2] === 'textarea' ? 1000 : 255;
					if (definition[2] !== 'textarea') { input.type = definition[2]; }
					if (definition[0] === 'title') { input.value = definition[4]; input.setAttribute('data-order-story-title', ''); }
					if (definition[0] === 'story') { input.rows = 3; input.placeholder = 'Ceritakan momen ini...'; }
					field.appendChild(label);
					field.appendChild(input);
					journeyGrid.appendChild(field);
				});
				journey.appendChild(journeyGrid);
				addJourney.before(journey);
				journey.querySelector('[data-order-story-title]').focus();
			}
			var removeStory = event.target.closest('[data-order-remove-journey]');
			if (removeStory && form.contains(removeStory)) {
				var addButton = removeStory.closest('[data-order-story]').querySelector('[data-order-add-journey]');
				removeStory.closest('[data-order-story-extra]').remove();
				addButton.focus();
			}
			if (toggle || close || add || removeButton || addJourney || removeStory) { queueDraft(); }
		});

		function stopDraft() {
			clearTimeout(draftTimer);
			draftTimer = null;
			window.removeEventListener('pagehide', flushDraft);
			document.removeEventListener('visibilitychange', hiddenDraft);
		}

		function draftStatus(text) {
			if (draftPanel) { draftPanel.classList.toggle('has-message', !!text); }
			if (draftMessage) { draftMessage.textContent = text; }
		}

		function draftUnavailable() {
			stopDraft();
			draftStorage = null;
			draftStatus('Draft tidak dapat disimpan di browser ini.');
		}

		function draftInputs() {
			return form.querySelectorAll('.apeiron-form-order__field input[name]:not([type="hidden"]):not([type="password"]):not([type="file"]), .apeiron-form-order__field select[name], .apeiron-form-order__field textarea[name]');
		}

		function inputKey(input) { return input.name.replace(/\[\]$/, ''); }

		function clearDraft() {
			clearTimeout(draftTimer);
			draftTimer = null;
			draftDirty = false;
			if (draftStorage) {
				try { draftStorage.removeItem(draftKey); } catch (error) { draftUnavailable(); }
			}
		}

		function flushDraft() {
			clearTimeout(draftTimer);
			draftTimer = null;
			if (!form.isConnected) { stopDraft(); return; }
			if (!draftStorage || restoringDraft || completed || !draftDirty) { return; }
			var values = Object.create(null);
			var optional = [];
			draftInputs().forEach(function (input) {
				var key = inputKey(input);
				if (key === 'website') { return; }
				if (input.type === 'checkbox') {
					if (!values[key]) { values[key] = []; }
					if (input.checked) { values[key].push(input.value); }
				} else if (input.type === 'radio') {
					if (!Object.prototype.hasOwnProperty.call(values, key)) { values[key] = ''; }
					if (input.checked) { values[key] = input.value; }
				} else { values[key] = input.value; }
				var group = input.closest('[data-order-optional]');
				if (group && !group.hidden && optional.indexOf(key) === -1) { optional.push(key); }
			});
			var now = Date.now();
			var draft = { version: 1, updatedAt: now, expiresAt: now + draftDays * 86400000, fields: values, optional: optional };
			if (draftSettings.saveStep) { draft.step = steps[current].getAttribute('data-order-step-key'); }
			try { draftStorage.setItem(draftKey, JSON.stringify(draft)); draftDirty = false; }
			catch (error) { draftUnavailable(); }
		}

		function queueDraft() {
			if (!draftStorage || restoringDraft || completed) { return; }
			draftDirty = true;
			clearTimeout(draftTimer);
			draftTimer = setTimeout(flushDraft, 400);
		}

		function hiddenDraft() { if (document.visibilityState === 'hidden') { flushDraft(); } }

		function restoreDraft() {
			if (!draftSettings.enabled || !config.postId || !config.documentId || !config.elementId ||
				(window.elementorFrontend && typeof window.elementorFrontend.isEditMode === 'function' && window.elementorFrontend.isEditMode())) { return 0; }
			draftKey = 'apeiron:form-order:draft:v1:' + [config.postId, config.documentId, config.elementId].map(function (id) { return encodeURIComponent(String(id)); }).join(':');
			var raw;
			try { draftStorage = window.localStorage; raw = draftStorage.getItem(draftKey); }
			catch (error) { draftUnavailable(); return 0; }
			window.addEventListener('pagehide', flushDraft);
			document.addEventListener('visibilitychange', hiddenDraft);
			if (!raw) { return 0; }
			var draft;
			try { draft = JSON.parse(raw); } catch (error) { clearDraft(); return 0; }
			if (!draft || draft.version !== 1 || !draft.fields || typeof draft.fields !== 'object' || Array.isArray(draft.fields) ||
				!Number.isFinite(draft.updatedAt) || !Number.isFinite(draft.expiresAt) || draft.expiresAt <= draft.updatedAt ||
				Date.now() >= Math.min(draft.expiresAt, draft.updatedAt + draftDays * 86400000)) { clearDraft(); return 0; }
			if (!draftSettings.restore) { return 0; }
			// Recreate only the built-in repeatable rows, using their field keys rather than row positions.
			[
				{ button: '[data-order-add-account]', row: '[data-order-account-row]', field: '[name^="bank_account_"]', pattern: /^bank_account_([3-9]|10)$/, prefix: 'bank_account_', suffix: '', start: 3, end: 10 },
				{ button: '[data-order-add-journey]', row: '[data-order-story-extra]', field: '[name$="_title"]', pattern: /^love_extra_([1-9]|10)_title$/, prefix: 'love_extra_', suffix: '_title', start: 1, end: 10 }
			].forEach(function (type) {
				var button = form.querySelector(type.button);
				if (!button) { return; }
				var numbers = Object.keys(draft.fields).filter(function (key) { return type.pattern.test(key); }).map(function (key) { return Number(key.match(type.pattern)[1]); });
				var maximum = Math.max.apply(Math, [0].concat(numbers));
				for (var number = type.start; number <= maximum && number <= type.end; number++) {
					if (!form.querySelector('[name="' + type.prefix + number + type.suffix + '"]')) { button.click(); }
				}
				form.querySelectorAll(type.row).forEach(function (row) {
					var input = row.querySelector(type.field);
					if (input && numbers.indexOf(Number(input.name.match(type.pattern)[1])) === -1) { row.remove(); }
				});
			});
			var restored = false;
			draftInputs().forEach(function (input) {
				var key = inputKey(input);
				if (key === 'website' || !Object.prototype.hasOwnProperty.call(draft.fields, key)) { return; }
				var value = draft.fields[key];
				if (input.type === 'checkbox' && Array.isArray(value)) { input.checked = value.indexOf(input.value) !== -1; }
				else if (input.type === 'radio' && typeof value === 'string') { input.checked = value === input.value; }
				else if (typeof value === 'string' && input.type !== 'radio' && input.type !== 'checkbox') {
					if (input.tagName === 'SELECT' && !Array.prototype.some.call(input.options, function (option) { return option.value === value; })) { return; }
					input.value = value;
				} else { return; }
				restored = true;
				if (input.hasAttribute('data-order-story-title')) { input.dispatchEvent(new Event('input', { bubbles: true })); }
			});
			form.querySelectorAll('[data-order-optional]').forEach(function (group) {
				var open = Array.isArray(draft.optional) && Array.prototype.some.call(group.querySelectorAll('[name]'), function (input) { return draft.optional.indexOf(inputKey(input)) !== -1; });
				group.hidden = !open;
				group.querySelectorAll('input, select, textarea').forEach(function (input) { input.disabled = !open; });
				var trigger = form.querySelector('[data-order-toggle-group="' + group.id + '"]');
				if (trigger) { trigger.hidden = open; trigger.setAttribute('aria-expanded', open ? 'true' : 'false'); }
			});
			if (restored && draftSettings.showNotice) { draftStatus('Data sebelumnya berhasil dipulihkan.'); }
			var index = draftSettings.saveStep && typeof draft.step === 'string' ? steps.findIndex(function (step) { return step.getAttribute('data-order-step-key') === draft.step; }) : -1;
			return index < 0 ? 0 : index;
		}

		if (draftSettings.enabled && draftClear) {
			draftClear.addEventListener('click', function () {
				if (busy || completed) { return; }
				restoringDraft = true;
				clearDraft();
				form.querySelectorAll('[data-order-account-row], [data-order-story-extra]').forEach(function (row) { row.remove(); });
				form.reset();
				form.querySelectorAll('[aria-invalid]').forEach(function (input) { input.removeAttribute('aria-invalid'); });
				form.querySelectorAll('[data-order-optional]').forEach(function (group) {
					group.hidden = true;
					group.querySelectorAll('input, select, textarea').forEach(function (input) { input.disabled = true; });
					var trigger = form.querySelector('[data-order-toggle-group="' + group.id + '"]');
					if (trigger) { trigger.hidden = false; trigger.setAttribute('aria-expanded', 'false'); }
				});
				form.querySelectorAll('[data-order-story-title]').forEach(function (input) { input.dispatchEvent(new Event('input', { bubbles: true })); });
				if (draftStorage) { draftStatus(''); }
				show(0);
				restoringDraft = false;
			});
			form.addEventListener('change', function (event) { if (event.target.closest('.apeiron-form-order__field')) { queueDraft(); } });
		}

		function successIcon(doc, className) {
			var icon = doc.createElement('span');
			icon.className = className;
			icon.setAttribute('aria-hidden', 'true');
			var check = doc.createElementNS('http://www.w3.org/2000/svg', 'svg');
			check.setAttribute('viewBox', '0 0 24 24');
			check.setAttribute('focusable', 'false');
			var tick = doc.createElementNS('http://www.w3.org/2000/svg', 'path');
			tick.setAttribute('d', 'M6 12l4 4 8-8');
			check.appendChild(tick);
			icon.appendChild(check);
			return icon;
		}

		function message(text, isError, link, isSuccess, isLoading) {
			notice.textContent = text;
			notice.hidden = !text;
			notice.classList.toggle('is-error', !!isError);
			notice.classList.toggle('is-success', !!isSuccess);
			notice.classList.toggle('is-loading', !!isLoading);
			var content = notice;
			if ((isSuccess || isLoading) && text) {
				var icon = isSuccess ? successIcon(document, 'apeiron-form-order__success-icon') : document.createElement('span');
				if (isLoading) { icon.className = 'apeiron-form-order__loading-icon'; icon.setAttribute('aria-hidden', 'true'); }
				content = document.createElement('div');
				content.className = 'apeiron-form-order__success-content';
				content.textContent = text;
				notice.textContent = '';
				notice.appendChild(icon);
				notice.appendChild(content);
			}
			if (link) {
				var anchor = document.createElement('a');
				anchor.href = link;
				anchor.target = '_blank';
				anchor.rel = 'noopener noreferrer';
				anchor.textContent = 'Buka WhatsApp';
				content.appendChild(document.createTextNode(' '));
				content.appendChild(anchor);
			}
		}

		function waitingTab(tab) {
			var doc = tab.document;
			doc.open();
			doc.write('<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"></head><body></body></html>');
			doc.close();
			doc.title = 'Pesanan sedang diproses';
			var stylesheet = document.querySelector('link[rel="stylesheet"][href*="/apeiron-form-order"]');
			if (stylesheet && stylesheet.sheet) {
				var style = doc.createElement('style');
				try {
					Array.prototype.forEach.call(stylesheet.sheet.cssRules, function (rule) {
						if (rule.cssText.indexOf('apeiron-form-order__waiting-') !== -1 || rule.cssText.indexOf('apeiron-order-waiting-spin') !== -1 || rule.cssText.indexOf('apeiron-order-success-') !== -1) {
							style.appendChild(doc.createTextNode(rule.cssText));
						}
					});
				} catch (error) {
					style = doc.createElement('link');
					style.rel = 'stylesheet';
					style.href = stylesheet.href;
				}
				doc.head.appendChild(style);
			}
			doc.body.className = 'apeiron-form-order__waiting-page';
			var card = doc.createElement('main');
			card.className = 'apeiron-form-order__waiting-card';
			card.setAttribute('role', 'status');
			var spinner = doc.createElement('span');
			spinner.className = 'apeiron-form-order__waiting-spinner';
			spinner.setAttribute('aria-hidden', 'true');
			var title = doc.createElement('h1');
			title.textContent = 'Pesanan sedang diproses';
			var description = doc.createElement('p');
			description.textContent = 'Mohon tunggu. WhatsApp akan terbuka otomatis setelah pesanan berhasil diproses.';
			card.appendChild(spinner);
			card.appendChild(title);
			card.appendChild(description);
			doc.body.appendChild(card);
		}

		function waitingSuccess(tab, text) {
			var doc = tab.document;
			doc.querySelector('.apeiron-form-order__waiting-spinner').replaceWith(successIcon(doc, 'apeiron-form-order__waiting-success'));
			doc.title = 'Pesanan berhasil diproses';
			doc.querySelector('h1').textContent = text || 'Pesanan berhasil diproses';
			doc.querySelector('p').textContent = 'WhatsApp akan segera terbuka.';
		}

		function openWhatsApp(target, url) {
			return new Promise(function (resolve, reject) {
				setTimeout(function () {
					try {
						if (target && target.closed) { throw new Error('Tab WhatsApp ditutup.'); }
						(target || window).location.assign(url);
						resolve();
					} catch (error) { reject(error); }
				}, 550);
			});
		}

		function show(index) {
			current = index;
			steps.forEach(function (step, i) { step.hidden = i !== current; });
			back.hidden = current === 0;
			next.hidden = current === steps.length - 1;
			submit.hidden = current !== steps.length - 1;
			progress.setAttribute('aria-valuenow', String(current + 1));
			progress.querySelector('span').style.width = ((current + 1) / steps.length * 100) + '%';
			form.querySelector('.apeiron-form-order__counter').textContent = 'Tahap ' + (current + 1) + ' dari ' + steps.length;
			message('');
			if (steps[current].querySelector('.apeiron-form-order__summary')) { buildSummary(steps[current]); }
			if (index > 0 && !restoringDraft) { steps[current].querySelector('h3').focus(); }
			queueDraft();
		}

		function validateStep(step) {
			var inputs = step.querySelectorAll('.apeiron-form-order__input');
			for (var i = 0; i < inputs.length; i++) {
				var input = inputs[i];
				if (input.disabled) { continue; }
				var valid = input.checkValidity() && (!input.required || String(input.value).trim() !== '');
				input.setAttribute('aria-invalid', valid ? 'false' : 'true');
				if (!valid) {
					message('');
					input.focus();
					input.reportValidity();
					return false;
				}
			}
			var groups = step.querySelectorAll('[data-order-required="yes"]');
			for (var j = 0; j < groups.length; j++) {
				if (groups[j].closest('[data-order-optional][hidden]')) { continue; }
				var checked = groups[j].querySelector('input:checked');
				groups[j].setAttribute('aria-invalid', checked ? 'false' : 'true');
				if (!checked) {
					message('');
					var first = groups[j].querySelector('input');
					if (first) { first.focus(); }
					return false;
				}
			}
			return true;
		}

		function fieldValue(field) {
			var selected = field.querySelectorAll('.apeiron-form-order__choices input:checked');
			if (selected.length) { return Array.prototype.map.call(selected, function (input) { return input.parentElement.textContent.trim(); }).join(', '); }
			var control = field.querySelector('.apeiron-form-order__input');
			if (!control) { return ''; }
			if (control.type === 'date' && /^\d{4}-\d{2}-\d{2}$/.test(control.value)) {
				var dateParts = control.value.split('-');
				var months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
				return Number(dateParts[2]) + ' ' + months[Number(dateParts[1]) - 1] + ' ' + dateParts[0];
			}
			return control.tagName === 'SELECT' && control.value ? control.options[control.selectedIndex].text : control.value;
		}

		function storyGroupHasContent(group) {
			return Array.prototype.some.call(group.querySelectorAll('[name$="_date"], [name$="_story"]'), function (input) {
				return !input.disabled && String(input.value).trim() !== '';
			});
		}

		function buildSummary(step) {
			var summary = step.querySelector('.apeiron-form-order__summary');
			summary.replaceChildren();
			steps.forEach(function (source) {
				if (source === step || source.querySelector('.apeiron-form-order__summary')) { return; }
				var section = document.createElement('section');
				section.className = 'apeiron-form-order__summary-section';
				var heading = document.createElement('h4');
				heading.textContent = source.querySelector('h3').textContent;
				section.appendChild(heading);
				var list = document.createElement('dl');
				list.className = 'apeiron-form-order__summary-list';
				var seenStoryGroups = new Set();
				source.querySelectorAll('.apeiron-form-order__field').forEach(function (field) {
					if (!field.querySelector('[name]:not(:disabled)')) { return; }
					var storyGroup = field.closest('[data-order-story-group]');
					if (storyGroup) {
						if (!storyGroupHasContent(storyGroup)) { return; }
						if (!seenStoryGroups.has(storyGroup)) {
							var subheading = document.createElement('div');
							subheading.className = 'apeiron-form-order__summary-subhead';
							var titleInput = storyGroup.querySelector('[data-order-story-title]');
							subheading.textContent = (titleInput && titleInput.value.trim()) || storyGroup.getAttribute('data-order-story-default-title') || '';
							list.appendChild(subheading);
							seenStoryGroups.add(storyGroup);
						}
						if (field.querySelector('[data-order-story-title]')) { return; }
					}
					var currentValue = fieldValue(field);
					if (source.hasAttribute('data-order-story') && !String(currentValue).trim()) { return; }
					var row = document.createElement('div');
					row.className = 'apeiron-form-order__summary-row';
					var label = document.createElement('dt');
					label.textContent = field.getAttribute('data-order-label');
					var value = document.createElement('dd');
					value.textContent = currentValue || '—';
					row.appendChild(label);
					row.appendChild(value);
					list.appendChild(row);
				});
				if (list.children.length || !source.hasAttribute('data-order-story')) {
					section.appendChild(list);
					summary.appendChild(section);
				}
			});
		}

		next.addEventListener('click', function () { if (validateStep(steps[current]) && current < steps.length - 1) { show(current + 1); } });
		back.addEventListener('click', function () { show(current - 1); });
		form.addEventListener('input', function (event) {
			if (event.target.matches('[name]')) { event.target.removeAttribute('aria-invalid'); }
			if (event.target.matches('[data-order-story-title]')) {
				var group = event.target.closest('[data-order-story-group]');
				group.querySelector('.apeiron-form-order__heading').textContent = event.target.value.trim() || group.getAttribute('data-order-story-default-title') || '';
			}
			if (event.target.closest('.apeiron-form-order__field')) { queueDraft(); }
		});
		form.addEventListener('submit', function (event) {
			event.preventDefault();
			if (busy || completed) { return; }
			queueDraft();
			flushDraft();
			for (var i = 0; i < steps.length; i++) {
				if (!validateStep(steps[i])) { show(i); validateStep(steps[i]); return; }
			}
			var fields = Object.create(null);
			steps.forEach(function (step) {
				step.querySelectorAll('.apeiron-form-order__field').forEach(function (field) {
					var inputs = field.querySelectorAll('[name]:not(:disabled)');
					if (!inputs.length) { return; }
					var key = inputs[0].name.replace(/\[\]$/, '');
					if (inputs[0].type === 'checkbox') {
						fields[key] = Array.prototype.filter.call(inputs, function (input) { return input.checked; }).map(function (input) { return input.value; });
					} else if (inputs[0].type === 'radio') {
						var selected = field.querySelector('input:checked');
						fields[key] = selected ? selected.value : '';
					} else { fields[key] = inputs[0].value.trim(); }
				});
			});
			var fieldSnapshot = JSON.stringify(fields);
			if (fieldSnapshot !== submissionFields) {
				var bytes = new Uint8Array(16);
				if (window.crypto && window.crypto.getRandomValues) { window.crypto.getRandomValues(bytes); }
				else { for (var b = 0; b < bytes.length; b++) { bytes[b] = Math.floor(Math.random() * 256); } }
				submissionId = Array.prototype.map.call(bytes, function (byte) { return ('0' + byte.toString(16)).slice(-2); }).join('');
				submissionFields = fieldSnapshot;
			}
			busy = true;
			var reservedTab = null;
			if (config.openNewTab) {
				try {
					reservedTab = window.open('about:blank', '_blank');
					if (reservedTab) { reservedTab.opener = null; waitingTab(reservedTab); }
				} catch (error) {
					if (reservedTab && !reservedTab.closed) { reservedTab.close(); }
					reservedTab = null;
				}
			}
			submit.disabled = true;
			back.disabled = true;
			if (draftClear) { draftClear.disabled = true; }
			message('Mengirim pesanan...', false, '', false, true);
			var headers = { 'Content-Type': 'application/json', 'X-Apeiron-Nonce': config.nonce };
			headers['X-Apeiron-Request-ID'] = submissionId;
			if (config.restNonce) { headers['X-WP-Nonce'] = config.restNonce; }
			fetch(config.endpoint, {
				method: 'POST',
				headers: headers,
				body: JSON.stringify({ post_id: config.postId, document_id: config.documentId, element_id: config.elementId, target_token: config.targetToken, website: form.querySelector('[name="website"]').value, fields: fields }),
				credentials: 'same-origin'
			}).then(function (response) {
				return response.json().catch(function () {
					throw new Error('Respons server tidak valid. Hubungi pengelola sebelum mengirim ulang.');
				}).then(function (data) {
					if (!data || typeof data !== 'object' || Array.isArray(data)) { throw new Error('Respons server tidak valid. Hubungi pengelola sebelum mengirim ulang.'); }
					if (!response.ok) { throw new Error(data.message || 'Pesanan gagal dikirim.'); }
					return data;
				});
			}).then(function (data) {
				if (draftSettings.clearSuccess) { clearDraft(); } else { flushDraft(); }
				completed = data;
				form.classList.add('is-complete');
				if (data.whatsapp_url) {
					if (data.whatsapp_behavior === 'same_tab') {
						if (reservedTab && !reservedTab.closed) { reservedTab.close(); }
						message(data.message || 'Pesanan berhasil diproses.', false, '', true);
						return openWhatsApp(null, data.whatsapp_url);
					}
					else {
						if (reservedTab && !reservedTab.closed) { waitingSuccess(reservedTab, data.message); }
						message(reservedTab && !reservedTab.closed ? data.message : 'Pesanan berhasil diproses. Klik tautan untuk membuka WhatsApp.', false, reservedTab && !reservedTab.closed ? '' : data.whatsapp_url, true);
						if (reservedTab && !reservedTab.closed) { return openWhatsApp(reservedTab, data.whatsapp_url); }
					}
				} else { if (reservedTab && !reservedTab.closed) { reservedTab.close(); } message(data.message || 'Pesanan berhasil dikirim.', false, '', true); }
			}).catch(function (error) {
				if (reservedTab && !reservedTab.closed) { reservedTab.close(); }
				if (completed) {
					message(completed.message || 'Pesanan berhasil diproses.', false, completed.whatsapp_url, true);
				} else { message(error instanceof TypeError ? 'Koneksi gagal. Coba lagi.' : (error.message || 'Koneksi gagal. Coba lagi.'), true); }
			}).finally(function () {
				busy = false; submit.disabled = !!completed; back.disabled = !!completed;
				if (draftClear) { draftClear.disabled = !!completed; }
			});
		});
		show(restoreDraft());
		restoringDraft = false;
	}

	function initScope(scope) {
		var node = scope && scope.jquery ? scope[0] : scope;
		if (!node) { return; }
		if (node.matches && node.matches('[data-apeiron-form-order]')) { init(node); }
		if (node.querySelectorAll) { node.querySelectorAll('[data-apeiron-form-order]').forEach(init); }
	}
	if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', function () { initScope(document); }); }
	else { initScope(document); }
	window.addEventListener('elementor/frontend/init', function () {
		if (window.elementorFrontend && elementorFrontend.hooks) {
			elementorFrontend.hooks.addAction('frontend/element_ready/apeiron-form-order.default', initScope);
		}
	});
	document.addEventListener('apeiron:content:loaded', function (event) { initScope(event.target); });
}());
