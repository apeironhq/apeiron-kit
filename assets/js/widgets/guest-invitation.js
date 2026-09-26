/** Guest Invitation Manager; SheetJS loads only when Excel import is used. */
(function () {
    'use strict';

    var toastContainer = null;

    function getToastContainer() {
        if (toastContainer && document.body.contains(toastContainer)) {
            return toastContainer;
        }
        toastContainer = document.createElement('div');
        toastContainer.className = 'apeiron-toast-container';
        document.body.appendChild(toastContainer);
        return toastContainer;
    }

    function showToast(message, type, duration) {
        type = type || 'info';
        duration = duration || 3000;

        var container = getToastContainer();
        var toast = document.createElement('div');
        toast.className = 'apeiron-toast is-' + type;
        toast.setAttribute('role', type === 'error' ? 'alert' : 'status');
        toast.setAttribute('aria-live', type === 'error' ? 'assertive' : 'polite');
        toast.textContent = message;
        container.appendChild(toast);

        setTimeout(function () {
            toast.classList.add('is-hiding');
            setTimeout(function () {
                if (toast.parentNode) toast.parentNode.removeChild(toast);
            }, 300);
        }, duration);
    }

    function escapeHtml(text) {
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function (m) { return map[m]; });
    }

    if (typeof window.apeironInvitationData === 'undefined') {
        window.apeironInvitationData = {};
    }

    var sheetJsPromise = null;
    var createStatusTimers = {};
    var widgetStates = new WeakMap();
    var MAX_IMPORT_FILE_BYTES = 10 * 1024 * 1024;
    var MAX_IMPORT_ROWS = 5000;

    function getSheetJsUrl() {
        if (window.ApeironGuestManager && window.ApeironGuestManager.sheetjsUrl) {
            return window.ApeironGuestManager.sheetjsUrl;
        }

		return '/wp-content/plugins/Apeiron/assets/vendor/xlsx.full.min.js';
    }

    function loadSheetJs() {
        if (typeof XLSX !== 'undefined') {
            return Promise.resolve();
        }

        if (sheetJsPromise) {
            return sheetJsPromise;
        }

        sheetJsPromise = new Promise(function (resolve, reject) {
            var existingScript = document.querySelector('script[data-apeiron-sheetjs]');
            if (existingScript) {
                if (typeof XLSX !== 'undefined') {
                    resolve();
                    return;
                }
                existingScript.addEventListener('load', function () {
                    if (typeof XLSX !== 'undefined') {
                        resolve();
                    } else {
                        reject(new Error('SheetJS loaded without XLSX global.'));
                    }
                });
                existingScript.addEventListener('error', reject);
                return;
            }

            var script = document.createElement('script');
            script.src = getSheetJsUrl();
            script.async = true;
            script.defer = true;
            script.setAttribute('data-apeiron-sheetjs', 'yes');
            script.onload = function () {
                if (typeof XLSX !== 'undefined') {
                    resolve();
                } else {
                    reject(new Error('SheetJS loaded without XLSX global.'));
                }
            };
            script.onerror = reject;
            document.head.appendChild(script);
        });

        return sheetJsPromise;
    }

    function normalizeText(value) {
        return (value || '').toString().trim().replace(/\s+/g, ' ');
    }

    function looksLikePhone(value) {
        var raw = normalizeText(value);
        var digits = raw.replace(/\D/g, '');
        return digits.length >= 7 && /^[+0-9][0-9\s().-]*$/.test(raw);
    }

    function normalizePhoneNumber(value) {
        var raw = normalizeText(value);
        if (!raw) return '';

        var digits = raw.replace(/\D/g, '');
        if (!digits) return '';

        if (digits.charAt(0) === '0') {
            return '62' + digits.substring(1);
        }

        return digits;
    }

    function formatGuestInputLine(name, phone) {
        var cleanName = normalizeText(name);
        var cleanPhone = normalizeText(phone);

        if (!cleanName) return '';
        return cleanPhone ? cleanName + ' | ' + cleanPhone : cleanName;
    }

    function parseGuestInputLine(line) {
        var raw = normalizeText(line);
        var guest = {
            raw: raw,
            name: raw,
            phone: '',
            phoneLabel: ''
        };

        if (!raw) return guest;

        var pipeIndex = raw.lastIndexOf('|');
        if (pipeIndex > -1) {
            guest.name = normalizeText(raw.substring(0, pipeIndex));
            guest.phoneLabel = normalizeText(raw.substring(pipeIndex + 1));
        } else {
            var dashIndex = raw.lastIndexOf(' - ');
            if (dashIndex > -1) {
                var possiblePhone = normalizeText(raw.substring(dashIndex + 3));
                if (looksLikePhone(possiblePhone)) {
                    guest.name = normalizeText(raw.substring(0, dashIndex));
                    guest.phoneLabel = possiblePhone;
                }
            }
        }

        guest.phone = normalizePhoneNumber(guest.phoneLabel);
        if (!guest.name) {
            guest.name = raw;
            guest.phone = '';
            guest.phoneLabel = '';
        }

        return guest;
    }

    function initTemplateButtons(container) {
        if (!container) return;
        var btns = container.querySelectorAll('.apeiron-btn-template');
        if (!btns.length) return;

        var activeBtn = container.querySelector('.apeiron-btn-template.is-active');
        if (!activeBtn) {
            activeBtn = btns[0];
            activeBtn.classList.add('is-active');
        }

        btns.forEach(function (btn) {
            btn.setAttribute('aria-pressed', btn.classList.contains('is-active') ? 'true' : 'false');
        });

        var widgetId = activeBtn.getAttribute('data-widget-id');
        var textarea = widgetId ? document.getElementById('message_template_' + widgetId) : null;
        var message = activeBtn.getAttribute('data-message') || '';
        if (textarea && !textarea.value.trim() && message) {
            textarea.value = message.replace(/\\n/g, '\n');
            if (window.apeironInvitationData[widgetId]) {
                window.apeironInvitationData[widgetId].template = textarea.value;
            }
        }
    }

    function updateDropzoneFileState(dropzone, fileInput) {
        if (!dropzone || !fileInput) return;

        var fileName = fileInput.files && fileInput.files.length ? fileInput.files[0].name : '';
        var fileLabel = dropzone.querySelector('.apeiron-dropzone-file');

        if (!fileLabel) {
            fileLabel = document.createElement('p');
            fileLabel.className = 'apeiron-dropzone-file';
            var content = dropzone.querySelector('.apeiron-dropzone-content');
            if (content) content.appendChild(fileLabel);
        }

        if (fileName) {
            dropzone.classList.add('has-file');
            fileLabel.textContent = fileName;
        } else {
            dropzone.classList.remove('has-file');
            fileLabel.textContent = '';
        }
    }

    function initDropzones(container) {
        var bindings = [];
        if (!container) return bindings;
        var dropzones = container.querySelectorAll('.apeiron-dropzone');
        dropzones.forEach(function (dropzone) {
            var fileInput = dropzone.querySelector('.apeiron-dropzone-input');
            if (!fileInput) return;

            if (dropzone.dataset.apeironReady === 'yes') {
                updateDropzoneFileState(dropzone, fileInput);
                return;
            }
            dropzone.dataset.apeironReady = 'yes';

            var dragEnter = function (e) {
                e.preventDefault();
                e.stopPropagation();
                dropzone.classList.add('is-dragging');
            };

            var dragOver = function (e) {
                e.preventDefault();
                e.stopPropagation();
                dropzone.classList.add('is-dragging');
            };

            var dragLeave = function (e) {
                e.preventDefault();
                e.stopPropagation();
                dropzone.classList.remove('is-dragging');
            };

            var drop = function (e) {
                e.preventDefault();
                e.stopPropagation();
                dropzone.classList.remove('is-dragging');
                if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length > 0) {
                    fileInput.files = e.dataTransfer.files;
                    updateDropzoneFileState(dropzone, fileInput);
                }
            };

            var change = function () {
                updateDropzoneFileState(dropzone, fileInput);
            };

            dropzone.addEventListener('dragenter', dragEnter);
            dropzone.addEventListener('dragover', dragOver);
            dropzone.addEventListener('dragleave', dragLeave);
            dropzone.addEventListener('drop', drop);
            fileInput.addEventListener('change', change);
            bindings.push({
                dropzone: dropzone,
                fileInput: fileInput,
                handlers: {
                    dragenter: dragEnter,
                    dragover: dragOver,
                    dragleave: dragLeave,
                    drop: drop,
                    change: change
                }
            });

            updateDropzoneFileState(dropzone, fileInput);
        });

        return bindings;
    }

    function destroyDropzones(bindings) {
        (bindings || []).forEach(function (binding) {
            if (!binding || !binding.dropzone || !binding.fileInput) {
                return;
            }

            binding.dropzone.removeEventListener('dragenter', binding.handlers.dragenter);
            binding.dropzone.removeEventListener('dragover', binding.handlers.dragover);
            binding.dropzone.removeEventListener('dragleave', binding.handlers.dragleave);
            binding.dropzone.removeEventListener('drop', binding.handlers.drop);
            binding.fileInput.removeEventListener('change', binding.handlers.change);
            binding.dropzone.dataset.apeironReady = 'no';
        });
    }

    window.apeironChangeTemplate = function (widgetId, type, template, buttonElement) {
        var textarea = document.getElementById('message_template_' + widgetId);
        if (textarea) {
            textarea.value = template.replace(/\\n/g, '\n');
            if (window.apeironInvitationData[widgetId]) {
                window.apeironInvitationData[widgetId].template = template.replace(/\\n/g, '\n');
            }
        }

        if (buttonElement && buttonElement.nodeType === 1) {
            var container = buttonElement.closest('.apeiron-invitation-wrapper');
            if (container) {
                var buttons = container.querySelectorAll('.apeiron-btn-template');
                buttons.forEach(function (btn) {
                    btn.classList.remove('is-active');
                    btn.setAttribute('aria-pressed', 'false');
                });
                buttonElement.classList.add('is-active');
                buttonElement.setAttribute('aria-pressed', 'true');
            }
        }
    };

    window.apeironImportExcel = function (widgetId) {
        var fileInput = document.getElementById('import_excel_' + widgetId);
        var importButton = document.querySelector('[data-apeiron-action="import"][data-widget-id="' + widgetId + '"]');
        if (!fileInput || !fileInput.files.length) {
            showToast('Silakan pilih file Excel terlebih dahulu.', 'warning');
            return;
        }

        var widgetContainer = document.getElementById('apeiron-invitation-' + widgetId);
        var importMode = widgetContainer ? widgetContainer.getAttribute('data-import-mode') : 'append';
        var skipHeader = !widgetContainer || widgetContainer.getAttribute('data-skip-header') !== 'no';
        importMode = importMode === 'replace' ? 'replace' : 'append';

        if (typeof XLSX === 'undefined') {
            if (importButton) {
                importButton.classList.add('is-loading');
                importButton.disabled = true;
                importButton.setAttribute('aria-busy', 'true');
            }
            showToast('Menyiapkan pembaca Excel. Impor akan berjalan otomatis.', 'info');
            loadSheetJs().then(function () {
                if (importButton) {
                    importButton.classList.remove('is-loading');
                    importButton.disabled = false;
                    importButton.removeAttribute('aria-busy');
                }
                window.apeironImportExcel(widgetId);
            }).catch(function () {
                if (importButton) {
                    importButton.classList.remove('is-loading');
                    importButton.disabled = false;
                    importButton.removeAttribute('aria-busy');
                }
                showToast('Gagal memuat pembaca Excel. Periksa koneksi lalu coba lagi.', 'error');
            });
            return;
        }

        var file = fileInput.files[0];
        if (file.size > MAX_IMPORT_FILE_BYTES) {
            showToast('File Excel terlalu besar. Maksimal 10 MB.', 'error');
            return;
        }
        var reader = new FileReader();

        if (importButton) {
            importButton.classList.add('is-loading');
            importButton.disabled = true;
            importButton.setAttribute('aria-busy', 'true');
        }

        reader.onload = function (e) {
            try {
                var data = new Uint8Array(e.target.result);
                var workbook = XLSX.read(data, { type: 'array' });

                var firstSheetName = workbook.SheetNames[0];
                var worksheet = workbook.Sheets[firstSheetName];

                var jsonData = XLSX.utils.sheet_to_json(worksheet, { header: 1, defval: '' });
                if (jsonData.length > MAX_IMPORT_ROWS) {
                    showToast('File Excel memiliki terlalu banyak baris. Maksimal 5.000 baris.', 'error');
                    return;
                }

                var guestNamesTextarea = document.getElementById('guest_names_' + widgetId);
                if (!guestNamesTextarea) {
                    showToast('Element tidak ditemukan. Silakan refresh halaman.', 'error');
                    return;
                }

                var guestNames = [];
                var phoneCount = 0;
                var startIndex = skipHeader ? 1 : 0;
                for (var i = startIndex; i < jsonData.length; i++) {
                    var row = jsonData[i];
                    if (row[0] && row[0].toString().trim()) {
                        var name = row[0].toString().trim();
                        var phone = row[1] && row[1].toString().trim() ? row[1].toString().trim() : '';
                        var guestLine = formatGuestInputLine(name, phone);

                        if (guestLine) {
                            guestNames.push(guestLine);
                            if (normalizePhoneNumber(phone)) {
                                phoneCount++;
                            }
                        }
                    }
                }

                if (guestNames.length === 0) {
                    showToast('Tidak ada data yang ditemukan di file Excel.', 'warning');
                    return;
                }

                var currentValue = guestNamesTextarea.value.trim();
                if (importMode === 'replace') {
                    guestNamesTextarea.value = guestNames.join('\n');
                } else {
                    var separator = currentValue ? '\n' : '';
                    guestNamesTextarea.value = currentValue + separator + guestNames.join('\n');
                }

                var importMessage = guestNames.length + ' tamu diimpor';
                if (phoneCount) {
                    importMessage += ' (' + phoneCount + ' dengan nomor WhatsApp)';
                }
                showToast(importMessage + '.', 'success');

                fileInput.value = '';
                var dropzone = fileInput.closest('.apeiron-dropzone');
                updateDropzoneFileState(dropzone, fileInput);
            } catch (error) {
                console.error('Error importing Excel:', error);
                showToast('Terjadi kesalahan saat membaca file Excel. Pastikan format valid (.xlsx / .xls).', 'error');
            } finally {
                if (importButton) {
                    importButton.classList.remove('is-loading');
                    importButton.disabled = false;
                    importButton.removeAttribute('aria-busy');
                }
            }
        };

        reader.onerror = function () {
            if (importButton) {
                importButton.classList.remove('is-loading');
                importButton.disabled = false;
                importButton.removeAttribute('aria-busy');
            }
            showToast('Gagal membaca file. Silakan coba lagi.', 'error');
        };

        reader.readAsArrayBuffer(file);
    };

    function normalizeBaseUrl(url) {
        try {
            var u = new URL(url);
            if (!u.pathname.endsWith('/')) {
                u.pathname += '/';
            }
            return u.toString();
        } catch (e) {
            var qIndex = url.indexOf('?');
            if (qIndex > -1) {
                var path = url.substring(0, qIndex);
                var query = url.substring(qIndex);
                if (!path.endsWith('/')) path += '/';
                return path + query;
            } else {
                if (!url.endsWith('/')) url += '/';
                return url;
            }
        }
    }

    function isValidInvitationUrl(url) {
        try {
            var parsed = new URL(url);
            var host = parsed.hostname;
            var isLocalhost = host === 'localhost';
            var isIpAddress = /^(?:\d{1,3}\.){3}\d{1,3}$/.test(host);
            return /^https?:$/i.test(parsed.protocol) && !!host && (host.indexOf('.') > -1 || isLocalhost || isIpAddress);
        } catch (e) {
            return false;
        }
    }

    function appendQueryParam(url, key, value) {
        try {
            var u = new URL(url);
            u.searchParams.set(key, value);
            return u.toString();
        } catch (e) {
            var separator = url.indexOf('?') > -1 ? '&' : '?';
            return url + separator + encodeURIComponent(key) + '=' + encodeURIComponent(value);
        }
    }

    function buildInvitationLink(baseLink, guestName) {
        var cleanName = guestName.trim();
        baseLink = normalizeBaseUrl(baseLink);

        try {
            var u = new URL(baseLink);
            u.searchParams.delete('to');
            baseLink = u.toString();
        } catch (e) {
            baseLink = baseLink.replace(/[?&]to=[^&]*/g, '');
            baseLink = baseLink.replace(/[?&]$/, '');
        }

        return appendQueryParam(baseLink, 'to', cleanName);
    }

    window.apeironCreateGuestList = function (widgetId) {
        var guestNamesTextarea = document.getElementById('guest_names_' + widgetId);
        var messageTemplate = document.getElementById('message_template_' + widgetId);
        var guestListTbody = document.getElementById('guest_list_' + widgetId);
        var linkInput = document.getElementById('invitation_link_input_' + widgetId);
        var createStatus = document.getElementById('create_status_' + widgetId);

        if (createStatusTimers[widgetId]) {
            clearTimeout(createStatusTimers[widgetId]);
            delete createStatusTimers[widgetId];
        }

        if (createStatus) {
            createStatus.textContent = '';
            createStatus.hidden = true;
        }

        if (!linkInput || !linkInput.value.trim()) {
            showToast('Silakan masukkan link undangan terlebih dahulu.', 'warning');
            if (linkInput) linkInput.focus();
            return;
        }

        if (!guestNamesTextarea || !guestNamesTextarea.value.trim()) {
            showToast('Silakan masukkan nama tamu terlebih dahulu.', 'warning');
            return;
        }

        if (!messageTemplate || !messageTemplate.value.trim()) {
            showToast('Silakan pilih template ucapan terlebih dahulu.', 'warning');
            return;
        }

        var baseInvitationLink = linkInput.value.trim();
        if (!/^https?:\/\//i.test(baseInvitationLink)) {
            baseInvitationLink = 'https://' + baseInvitationLink;
        }

        if (!isValidInvitationUrl(baseInvitationLink)) {
            showToast('Link undangan belum valid. Contoh: apeiron.id/ika-budi atau https://apeiron.id/ika-budi', 'error');
            linkInput.focus();
            return;
        }

        var template = messageTemplate.value;

        baseInvitationLink = normalizeBaseUrl(baseInvitationLink);

        var parsedRows = guestNamesTextarea.value.split('\n').map(function (line) {
            return parseGuestInputLine(line);
        }).filter(function (guest) {
            return guest.name.length > 0;
        });

        var seenGuests = {};
        var duplicateCount = 0;
        var guestRows = parsedRows.filter(function (guest) {
            var key = guest.name.toLowerCase() + '|' + guest.phone;
            if (seenGuests[key]) {
                duplicateCount++;
                return false;
            }
            seenGuests[key] = true;
            return true;
        });

        if (!guestRows.length) {
            showToast('Tidak ada nama tamu valid yang bisa dibuat.', 'warning');
            return;
        }

        if (!window.apeironInvitationData[widgetId]) {
            window.apeironInvitationData[widgetId] = { template: '', guests: [] };
        }

        window.apeironInvitationData[widgetId].guests = guestRows.map(function (guest) {
            var guestLink = buildInvitationLink(baseInvitationLink, guest.name);

            var personalizedMessage = template
                .replace(/\[nama\]/g, guest.name)
                .replace(/\[link-undangan\]/g, guestLink);

            return {
                name: guest.name,
                phone: guest.phone,
                phoneLabel: guest.phoneLabel,
                message: personalizedMessage,
                link: guestLink
            };
        });

        window.apeironInvitationData[widgetId].template = template;

        // User data uses textContent; innerHTML below contains static SVG only.
        if (guestListTbody) {
            guestListTbody.innerHTML = '';

            var safeWidgetId = escapeHtml(widgetId);
            window.apeironInvitationData[widgetId].guests.forEach(function (guest, index) {
                var tr = document.createElement('tr');

                var tdNum = document.createElement('td');
                tdNum.textContent = index + 1;
                tr.appendChild(tdNum);

                var tdName = document.createElement('td');
                var guestName = document.createElement('span');
                guestName.className = 'apeiron-guest-name';
                guestName.textContent = guest.name;
                tdName.appendChild(guestName);

                if (guest.phoneLabel || guest.phone) {
                    var guestPhone = document.createElement('span');
                    guestPhone.className = 'apeiron-guest-phone';
                    guestPhone.textContent = guest.phoneLabel || ('+' + guest.phone);
                    tdName.appendChild(guestPhone);
                }
                tr.appendChild(tdName);

                var tdActions = document.createElement('td');
                var actionWrap = document.createElement('div');
                actionWrap.className = 'apeiron-action-buttons';
                actionWrap.innerHTML =
                    '<button type="button" class="apeiron-action-btn apeiron-btn-wa" data-action="wa" data-widget="' + safeWidgetId + '" data-index="' + index + '" title="Kirim via WhatsApp" aria-label="Kirim via WhatsApp">' +
                    '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>' +
                    '</button>' +
                    '<button type="button" class="apeiron-action-btn apeiron-btn-fb" data-action="fb" data-widget="' + safeWidgetId + '" data-index="' + index + '" title="Bagikan ke Facebook" aria-label="Bagikan ke Facebook">' +
                    '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>' +
                    '</button>' +
                    '<button type="button" class="apeiron-action-btn apeiron-btn-ig" data-action="ig" data-widget="' + safeWidgetId + '" data-index="' + index + '" title="Salin untuk Instagram" aria-label="Salin untuk Instagram">' +
                    '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>' +
                    '</button>' +
                    '<button type="button" class="apeiron-action-btn apeiron-btn-copy" data-action="copy" data-widget="' + safeWidgetId + '" data-index="' + index + '" title="Salin pesan" aria-label="Salin pesan">' +
                    '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>' +
                    '</button>';
                tdActions.appendChild(actionWrap);
                tr.appendChild(tdActions);

                guestListTbody.appendChild(tr);
            });
        } else {
            showToast('Daftar tamu sudah diproses, tetapi tabel hasil sedang disembunyikan di pengaturan widget.', 'warning');
            return;
        }

        if (createStatus) {
            createStatus.textContent = 'Berhasil membuat ' + guestRows.length + ' nama tamu' + (duplicateCount ? '. ' + duplicateCount + ' duplikat dilewati.' : '.');
            createStatus.hidden = false;
            createStatusTimers[widgetId] = setTimeout(function () {
                createStatus.hidden = true;
                createStatus.textContent = '';
                delete createStatusTimers[widgetId];
            }, 3500);
        }
    };

    window.apeironShareWA = function (widgetId, guestIndex) {
        var guest = window.apeironInvitationData[widgetId] && window.apeironInvitationData[widgetId].guests[guestIndex];
        if (!guest) return;

        var waMessage = encodeURIComponent(guest.message);
        var waUrl = guest.phone
            ? 'https://wa.me/' + encodeURIComponent(guest.phone) + '?text=' + waMessage
            : 'https://wa.me/?text=' + waMessage;
        window.open(waUrl, '_blank', 'noopener,noreferrer');
    };

    window.apeironShareFB = function (widgetId, guestIndex) {
        var guest = window.apeironInvitationData[widgetId] && window.apeironInvitationData[widgetId].guests[guestIndex];
        if (!guest) return;

        var shareUrl = encodeURIComponent(guest.link);
        var shareText = encodeURIComponent(guest.message);
        var fbUrl = 'https://www.facebook.com/sharer/sharer.php?u=' + shareUrl + '&quote=' + shareText;
        window.open(fbUrl, '_blank', 'width=626,height=436,noopener,noreferrer');
    };

    window.apeironShareIG = function (widgetId, guestIndex) {
        var guest = window.apeironInvitationData[widgetId] && window.apeironInvitationData[widgetId].guests[guestIndex];
        if (!guest) return;

        var shareContent = guest.message + '\n\n' + guest.link;
        copyToClipboard(shareContent, 'Konten berhasil disalin. Silakan tempel di Instagram.');
    };

    window.apeironCopyMessage = function (widgetId, guestIndex) {
        var guest = window.apeironInvitationData[widgetId] && window.apeironInvitationData[widgetId].guests[guestIndex];
        if (!guest) return;

        copyToClipboard(guest.message, 'Pesan berhasil disalin.');
    };

    function copyToClipboard(text, successMessage) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(function () {
                showToast(successMessage, 'success');
            }).catch(function () {
                fallbackCopy(text, successMessage);
            });
        } else {
            fallbackCopy(text, successMessage);
        }
    }

    function fallbackCopy(text, successMessage) {
        var textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.style.cssText = 'position:fixed;left:-9999px;top:-9999px;';
        document.body.appendChild(textarea);
        textarea.select();
        try {
            document.execCommand('copy');
            showToast(successMessage, 'success');
        } catch (e) {
            showToast('Gagal menyalin teks.', 'error');
        }
        document.body.removeChild(textarea);
    }

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.apeiron-action-btn[data-action]');
        if (!btn) return;

        var action = btn.getAttribute('data-action');
        var widgetId = btn.getAttribute('data-widget');
        var index = parseInt(btn.getAttribute('data-index'), 10);

        if (!action || !widgetId || isNaN(index)) return;

        switch (action) {
            case 'wa':
                window.apeironShareWA(widgetId, index);
                break;
            case 'fb':
                window.apeironShareFB(widgetId, index);
                break;
            case 'ig':
                window.apeironShareIG(widgetId, index);
                break;
            case 'copy':
                window.apeironCopyMessage(widgetId, index);
                break;
        }
    });

    // Delegation survives Elementor preview re-renders.
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-apeiron-action][data-widget-id]');
        if (!btn) return;

        var action = btn.getAttribute('data-apeiron-action');
        var widgetId = btn.getAttribute('data-widget-id');
        if (!action || !widgetId) return;

        if (action === 'import') {
            window.apeironImportExcel(widgetId);
        } else if (action === 'create') {
            window.apeironCreateGuestList(widgetId);
        }
    });

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.apeiron-btn-template[data-message]');
        if (!btn) return;

        var widgetId = btn.getAttribute('data-widget-id');
        var templateId = btn.getAttribute('data-template-id');
        var message = btn.getAttribute('data-message');

        if (!widgetId || !message) return;

        window.apeironChangeTemplate(widgetId, templateId, message, btn);
    });

    function destroyWidget(container) {
        var state = widgetStates.get(container);
        if (!state) return;

        destroyDropzones(state.dropzones);
        if (state.widgetId && createStatusTimers[state.widgetId]) {
            clearTimeout(createStatusTimers[state.widgetId]);
            delete createStatusTimers[state.widgetId];
        }
        widgetStates.delete(container);
    }

    function initWidget(container) {
        if (!container || widgetStates.has(container)) return;

        var widgetId = container.id.replace('apeiron-invitation-', '');
        if (widgetId && !window.apeironInvitationData[widgetId]) {
            window.apeironInvitationData[widgetId] = {
                template: '',
                guests: []
            };
        }

        initTemplateButtons(container);
        var dropzones = initDropzones(container);
        widgetStates.set(container, {
            widgetId: widgetId,
            dropzones: dropzones
        });
    }

    function getWidgetContainers(scope) {
        var root = scope && scope.jquery ? scope[0] : (scope || document);
        if (!root) return [];

        var containers = [];
        if (root.matches && root.matches('.apeiron-invitation-container')) {
            containers.push(root);
        }
        if (root.querySelectorAll) {
            containers = containers.concat(Array.from(root.querySelectorAll('.apeiron-invitation-container')));
        }
        return containers;
    }

    function initAllWidgets(scope) {
        var containers = getWidgetContainers(scope);
        containers.forEach(function (container) {
            initWidget(container);
        });
    }

    var api = {
        init: function () {
            initAllWidgets(document);
        },
        initScope: initAllWidgets,
        destroy: destroyWidget
    };

    window.ApeironGuestManager = window.ApeironGuestManager || {};
    window.ApeironGuestManager.init = api.init;
    window.ApeironGuestManager.initScope = api.initScope;
    window.ApeironGuestManager.destroy = api.destroy;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', api.init);
    } else {
        api.init();
    }

    window.addEventListener('elementor/frontend/init', function () {
        if (typeof elementorFrontend === 'undefined' || !elementorFrontend.hooks) {
            return;
        }

        elementorFrontend.hooks.addAction(
            'frontend/element_ready/apeiron-guest-invitation-manager.default',
            initAllWidgets
        );
    });

    if (window.ApeironGuestManagerTestMode) {
        window.ApeironGuestManagerTestApi = Object.freeze({
            buildInvitationLink: buildInvitationLink,
            formatGuestInputLine: formatGuestInputLine,
            isValidInvitationUrl: isValidInvitationUrl,
            normalizePhoneNumber: normalizePhoneNumber,
            normalizeText: normalizeText,
            parseGuestInputLine: parseGuestInputLine
        });
    }

})();
