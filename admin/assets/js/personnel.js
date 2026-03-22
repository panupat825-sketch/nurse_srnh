(function () {
    'use strict';

    var root = document.getElementById('personnelPage');
    if (!root) {
        return;
    }

    var endpoints = {
        load: root.getAttribute('data-load-url') || '',
        saveLayout: root.getAttribute('data-save-layout-url') || '',
        saveConnection: root.getAttribute('data-save-connection-url') || '',
        deleteConnection: root.getAttribute('data-delete-connection-url') || '',
        save: root.getAttribute('data-save-url') || '',
        del: root.getAttribute('data-delete-url') || ''
    };

    var state = {
        personnel: [],
        connections: [],
        positions: {},
        lines: [],
        connectMode: false,
        pendingSourceId: null,
        dirty: false
    };

    var refs = {
        canvas: document.getElementById('personnelCanvas'),
        canvasWrapper: document.getElementById('canvasWrapper'),
        searchInput: document.getElementById('searchInput'),
        departmentFilter: document.getElementById('departmentFilter'),
        positionFilter: document.getElementById('positionFilter'),
        statusFilter: document.getElementById('statusFilter'),
        btnApplyFilter: document.getElementById('btnApplyFilter'),
        btnClearFilter: document.getElementById('btnClearFilter'),
        btnSaveLayout: document.getElementById('btnSaveLayout'),
        btnAutoArrange: document.getElementById('btnAutoArrange'),
        btnResetLayout: document.getElementById('btnResetLayout'),
        btnConnectMode: document.getElementById('btnConnectMode'),
        btnAddPersonnel: document.getElementById('btnAddPersonnel'),
        layoutDirtyBadge: document.getElementById('layoutDirtyBadge'),
        connectionList: document.getElementById('connectionList'),
        deleteForm: document.getElementById('personnelDeleteForm'),
        deleteIdInput: document.getElementById('deletePersonnelId'),
        modalLabel: document.getElementById('personnelModalLabel'),
        form: document.getElementById('personnelForm'),
        formId: document.getElementById('personnel_id'),
        formExistingImage: document.getElementById('existing_profile_image'),
        formFullName: document.getElementById('full_name'),
        formPositionName: document.getElementById('position_name'),
        formDepartmentName: document.getElementById('department_name'),
        formUnitName: document.getElementById('unit_name'),
        formPhone: document.getElementById('phone'),
        formInternalPhone: document.getElementById('internal_phone'),
        formSortOrder: document.getElementById('sort_order'),
        formStatus: document.getElementById('status'),
        formParentId: document.getElementById('parent_id'),
        formNote: document.getElementById('note'),
        formImageInput: document.getElementById('profile_image_file'),
        currentImageWrap: document.getElementById('currentImageWrap'),
        currentImagePreview: document.getElementById('currentImagePreview'),
        removeProfileImage: document.getElementById('remove_profile_image')
    };

    var bsModalElement = document.getElementById('personnelModal');
    var bsModal = bsModalElement && window.bootstrap ? new window.bootstrap.Modal(bsModalElement) : null;

    function escapeHtml(value) {
        var div = document.createElement('div');
        div.innerText = value == null ? '' : String(value);
        return div.innerHTML;
    }

    function setDirty(flag) {
        state.dirty = !!flag;
        if (refs.layoutDirtyBadge) {
            refs.layoutDirtyBadge.classList.toggle('d-none', !state.dirty);
        }
    }

    function showMessage(message) {
        window.alert(message);
    }

    function toInt(value, fallback) {
        var parsed = parseInt(value, 10);
        return Number.isNaN(parsed) ? fallback : parsed;
    }

    function validateClientImageFile() {
        if (!refs.formImageInput || !refs.formImageInput.files || refs.formImageInput.files.length === 0) {
            return true;
        }

        var file = refs.formImageInput.files[0];
        var maxSize = 5 * 1024 * 1024;
        var allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        var fileName = (file.name || '').toLowerCase();
        var ext = fileName.indexOf('.') > -1 ? fileName.split('.').pop() : '';
        var allowedExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (file.size > maxSize) {
            showMessage('ไฟล์รูปต้องมีขนาดไม่เกิน 5MB');
            refs.formImageInput.value = '';
            return false;
        }

        if (allowedTypes.indexOf(file.type) === -1 && allowedExt.indexOf(ext) === -1) {
            showMessage('รองรับเฉพาะไฟล์ jpg, jpeg, png, gif, webp เท่านั้น');
            refs.formImageInput.value = '';
            return false;
        }

        return true;
    }
    function getFilterParams() {
        return {
            search: refs.searchInput ? refs.searchInput.value.trim() : '',
            department: refs.departmentFilter ? refs.departmentFilter.value.trim() : '',
            position: refs.positionFilter ? refs.positionFilter.value.trim() : '',
            status: refs.statusFilter ? refs.statusFilter.value : 'all'
        };
    }

    function fetchJson(url, options) {
        return fetch(url, options || {}).then(function (response) {
            return response.json().catch(function () {
                return { success: false, message: 'Invalid JSON response' };
            });
        });
    }

    function postJson(url, payload) {
        return fetchJson(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(payload)
        });
    }

    function loadData() {
        var params = new URLSearchParams(getFilterParams());
        return fetchJson(endpoints.load + '?' + params.toString())
            .then(function (res) {
                if (!res || !res.success) {
                    throw new Error((res && res.message) || 'ไม่สามารถโหลดข้อมูลได้');
                }

                state.personnel = Array.isArray(res.personnel) ? res.personnel : [];
                state.connections = Array.isArray(res.connections) ? res.connections : [];
                ensurePositions();
                renderCards();
                renderConnectionList();
                drawConnections();
                setDirty(false);
            })
            .catch(function (err) {
                showMessage(err.message || 'ไม่สามารถโหลดข้อมูลได้');
            });
    }

    function ensurePositions() {
        var spacingX = 250;
        var spacingY = 170;
        var startX = 20;
        var startY = 20;

        state.positions = {};

        state.personnel.forEach(function (item, index) {
            var hasX = item.x_position !== null && item.x_position !== undefined;
            var hasY = item.y_position !== null && item.y_position !== undefined;

            var x = hasX ? toInt(item.x_position, 0) : startX + ((index % 4) * spacingX);
            var y = hasY ? toInt(item.y_position, 0) : startY + (Math.floor(index / 4) * spacingY);

            if (x < 0) {
                x = 0;
            }
            if (y < 0) {
                y = 0;
            }

            state.positions[item.id] = { x: x, y: y };
        });
    }

    function personImageHtml(person) {
        if (person.profile_image && person.profile_image.trim() !== '') {
            return '<img src="/nurse_srnh/' + escapeHtml(person.profile_image) + '" alt="profile">';
        }

        return '<div class="personnel-avatar-placeholder">' + escapeHtml((person.full_name || '?').charAt(0)) + '</div>';
    }

    function personCardHtml(person) {
        var position = state.positions[person.id] || { x: 0, y: 0 };
        var statusBadge = Number(person.status) === 1
            ? '<span class="badge text-bg-success">Active</span>'
            : '<span class="badge text-bg-secondary">Inactive</span>';

        return [
            '<div class="personnel-card" data-id="', escapeHtml(person.id), '" style="left:', escapeHtml(position.x), 'px; top:', escapeHtml(position.y), 'px;">',
            '   <div class="personnel-card-head">',
            '       <div class="personnel-avatar">', personImageHtml(person), '</div>',
            '       <div class="personnel-main">',
            '           <div class="personnel-name">', escapeHtml(person.full_name || ''), '</div>',
            '           <div class="personnel-position">', escapeHtml(person.position_name || '-'), '</div>',
            '           <div class="personnel-meta">',
            '               <span>', escapeHtml(person.department_name || '-'), '</span>',
            '               <span class="dot">•</span>',
            '               <span>', escapeHtml(person.unit_name || '-'), '</span>',
            '           </div>',
            '       </div>',
            '   </div>',
            '   <div class="personnel-card-foot">',
            '       <div class="small text-soft">',
            '           โทร: ', escapeHtml(person.phone || '-'), ' | ภายใน: ', escapeHtml(person.internal_phone || '-'),
            '       </div>',
            '       <div class="d-flex flex-wrap gap-1 mt-2">',
            '           ', statusBadge,
            '           <button type="button" class="btn btn-sm btn-outline-primary js-edit">แก้ไข</button>',
            '           <button type="button" class="btn btn-sm btn-outline-danger js-delete">ลบ</button>',
            '           <button type="button" class="btn btn-sm btn-outline-secondary js-link">เชื่อม</button>',
            '       </div>',
            '   </div>',
            '</div>'
        ].join('');
    }

    function renderCards() {
        if (!refs.canvas) {
            return;
        }

        refs.canvas.innerHTML = state.personnel.map(personCardHtml).join('');

        bindCardEvents();
        initDrag();
        adjustCanvasSize();
    }

    function adjustCanvasSize() {
        if (!refs.canvas) {
            return;
        }

        var maxBottom = 680;

        state.personnel.forEach(function (person) {
            var pos = state.positions[person.id] || { x: 0, y: 0 };
            var bottom = pos.y + 170;
            if (bottom > maxBottom) {
                maxBottom = bottom;
            }
        });

        refs.canvas.style.minHeight = maxBottom + 'px';
    }

    function findPersonById(id) {
        var targetId = toInt(id, 0);
        for (var i = 0; i < state.personnel.length; i++) {
            if (toInt(state.personnel[i].id, 0) === targetId) {
                return state.personnel[i];
            }
        }
        return null;
    }

    function bindCardEvents() {
        var cards = refs.canvas.querySelectorAll('.personnel-card');

        cards.forEach(function (card) {
            var id = toInt(card.getAttribute('data-id'), 0);

            card.addEventListener('click', function (event) {
                var button = event.target.closest('button');
                if (button) {
                    return;
                }

                if (state.connectMode) {
                    selectForConnection(id);
                }
            });

            var editBtn = card.querySelector('.js-edit');
            if (editBtn) {
                editBtn.addEventListener('click', function (event) {
                    event.preventDefault();
                    event.stopPropagation();
                    openEditModal(id);
                });
            }

            var deleteBtn = card.querySelector('.js-delete');
            if (deleteBtn) {
                deleteBtn.addEventListener('click', function (event) {
                    event.preventDefault();
                    event.stopPropagation();
                    deletePersonnel(id);
                });
            }

            var linkBtn = card.querySelector('.js-link');
            if (linkBtn) {
                linkBtn.addEventListener('click', function (event) {
                    event.preventDefault();
                    event.stopPropagation();
                    state.connectMode = true;
                    if (refs.btnConnectMode) {
                        refs.btnConnectMode.classList.add('active');
                    }
                    selectForConnection(id);
                });
            }
        });
    }

    function clearLines() {
        state.lines.forEach(function (item) {
            if (item && item.line && typeof item.line.remove === 'function') {
                item.line.remove();
            }
        });
        state.lines = [];
    }

    function drawConnections() {
        clearLines();

        if (typeof window.LeaderLine === 'undefined') {
            return;
        }

        state.connections.forEach(function (conn) {
            var sourceEl = refs.canvas.querySelector('.personnel-card[data-id="' + conn.source_personnel_id + '"]');
            var targetEl = refs.canvas.querySelector('.personnel-card[data-id="' + conn.target_personnel_id + '"]');

            if (!sourceEl || !targetEl) {
                return;
            }

            var options = {
                color: '#5f7d86',
                size: 2,
                startSocket: 'right',
                endSocket: 'left',
                path: 'straight',
                endPlug: 'arrow3'
            };

            if (conn.line_style === 'dashed' || conn.line_style === 'dotted') {
                options.dash = {
                    animation: false,
                    len: conn.line_style === 'dotted' ? 2 : 6,
                    gap: conn.line_style === 'dotted' ? 4 : 4
                };
            }

            var line = new window.LeaderLine(sourceEl, targetEl, options);
            state.lines.push({ id: conn.id, line: line });
        });
    }

    function refreshLinePositions() {
        state.lines.forEach(function (item) {
            if (item && item.line && typeof item.line.position === 'function') {
                item.line.position();
            }
        });
    }

    function initDrag() {
        if (typeof window.interact === 'undefined') {
            return;
        }

        try {
            window.interact('.personnel-card').unset();
        } catch (e) {
        }

        window.interact('.personnel-card').draggable({
            listeners: {
                move: function (event) {
                    var target = event.target;
                    var id = toInt(target.getAttribute('data-id'), 0);
                    var rect = refs.canvas.getBoundingClientRect();

                    var x = toInt(target.style.left, 0) + event.dx;
                    var y = toInt(target.style.top, 0) + event.dy;

                    if (x < 0) {
                        x = 0;
                    }
                    if (y < 0) {
                        y = 0;
                    }

                    var maxX = Math.max(0, rect.width - target.offsetWidth);
                    if (x > maxX) {
                        x = maxX;
                    }

                    target.style.left = Math.round(x) + 'px';
                    target.style.top = Math.round(y) + 'px';

                    state.positions[id] = {
                        x: Math.round(x),
                        y: Math.round(y)
                    };

                    setDirty(true);
                    refreshLinePositions();
                    adjustCanvasSize();
                }
            }
        });
    }

    function openAddModal() {
        if (!refs.form) {
            return;
        }

        refs.form.reset();
        refs.formId.value = '';
        refs.formExistingImage.value = '';
        refs.modalLabel.textContent = 'เพิ่มบุคลากร';
        refs.formSortOrder.value = '0';
        refs.formStatus.value = '1';
        refs.currentImageWrap.classList.add('d-none');
        refs.currentImagePreview.setAttribute('src', '');
        refs.removeProfileImage.checked = false;
        renderParentOptions('', null);

        if (bsModal) {
            bsModal.show();
        }
    }

    function openEditModal(id) {
        var person = findPersonById(id);
        if (!person) {
            return;
        }

        refs.formId.value = String(person.id);
        refs.formExistingImage.value = person.profile_image || '';
        refs.formFullName.value = person.full_name || '';
        refs.formPositionName.value = person.position_name || '';
        refs.formDepartmentName.value = person.department_name || '';
        refs.formUnitName.value = person.unit_name || '';
        refs.formPhone.value = person.phone || '';
        refs.formInternalPhone.value = person.internal_phone || '';
        refs.formSortOrder.value = String(person.sort_order || 0);
        refs.formStatus.value = String(person.status || 0);
        refs.formNote.value = person.note || '';
        refs.formImageInput.value = '';
        refs.removeProfileImage.checked = false;
        refs.modalLabel.textContent = 'แก้ไขบุคลากร #' + person.id;

        renderParentOptions(person.parent_id, person.id);

        if (person.profile_image && person.profile_image.trim() !== '') {
            refs.currentImageWrap.classList.remove('d-none');
            refs.currentImagePreview.setAttribute('src', '/nurse_srnh/' + person.profile_image);
        } else {
            refs.currentImageWrap.classList.add('d-none');
            refs.currentImagePreview.setAttribute('src', '');
        }

        if (bsModal) {
            bsModal.show();
        }
    }

    function renderParentOptions(selectedId, currentId) {
        if (!refs.formParentId) {
            return;
        }

        var selected = selectedId == null ? '' : String(selectedId);
        var html = ['<option value="">- ไม่ระบุ -</option>'];

        state.personnel.forEach(function (person) {
            if (currentId !== null && toInt(person.id, 0) === toInt(currentId, 0)) {
                return;
            }

            var id = String(person.id);
            html.push('<option value="' + escapeHtml(id) + '" ' + (selected === id ? 'selected' : '') + '>' + escapeHtml(person.full_name) + '</option>');
        });

        refs.formParentId.innerHTML = html.join('');
    }

    function deletePersonnel(id) {
        if (!refs.deleteForm || !refs.deleteIdInput) {
            return;
        }

        var person = findPersonById(id);
        var name = person ? person.full_name : ('#' + id);
        if (!window.confirm('ยืนยันการลบข้อมูลบุคลากร: ' + name + ' ?')) {
            return;
        }

        refs.deleteIdInput.value = String(id);
        refs.deleteForm.submit();
    }

    function selectForConnection(id) {
        var source = state.pendingSourceId;
        var cards = refs.canvas.querySelectorAll('.personnel-card');

        cards.forEach(function (card) {
            card.classList.remove('link-source');
        });

        if (!source) {
            state.pendingSourceId = id;
            var sourceCard = refs.canvas.querySelector('.personnel-card[data-id="' + id + '"]');
            if (sourceCard) {
                sourceCard.classList.add('link-source');
            }
            return;
        }

        if (source === id) {
            state.pendingSourceId = null;
            return;
        }

        saveConnection(source, id);
        state.pendingSourceId = null;
    }

    function saveConnection(sourceId, targetId) {
        postJson(endpoints.saveConnection, {
            source_personnel_id: sourceId,
            target_personnel_id: targetId,
            relation_type: 'direct',
            line_style: 'solid'
        }).then(function (res) {
            if (!res || !res.success) {
                showMessage((res && res.message) || 'ไม่สามารถบันทึกเส้นเชื่อมได้');
                return;
            }
            loadData();
        }).catch(function () {
            showMessage('ไม่สามารถบันทึกเส้นเชื่อมได้');
        });
    }

    function deleteConnection(connectionId) {
        if (!window.confirm('ยืนยันการลบเส้นเชื่อมนี้?')) {
            return;
        }

        postJson(endpoints.deleteConnection, { id: connectionId })
            .then(function (res) {
                if (!res || !res.success) {
                    showMessage((res && res.message) || 'ไม่สามารถลบเส้นเชื่อมได้');
                    return;
                }
                loadData();
            })
            .catch(function () {
                showMessage('ไม่สามารถลบเส้นเชื่อมได้');
            });
    }

    function renderConnectionList() {
        if (!refs.connectionList) {
            return;
        }

        if (!state.connections.length) {
            refs.connectionList.innerHTML = '<div class="small text-muted">ยังไม่มีเส้นเชื่อม</div>';
            return;
        }

        var html = [];

        state.connections.forEach(function (conn) {
            var source = findPersonById(conn.source_personnel_id);
            var target = findPersonById(conn.target_personnel_id);

            if (!source || !target) {
                return;
            }

            html.push(
                '<div class="connection-item">' +
                '   <div class="small fw-semibold">' + escapeHtml(source.full_name) + '</div>' +
                '   <div class="small text-soft">ไปยัง: ' + escapeHtml(target.full_name) + '</div>' +
                '   <button type="button" class="btn btn-sm btn-outline-danger mt-1 js-delete-connection" data-id="' + escapeHtml(conn.id) + '">ลบเส้น</button>' +
                '</div>'
            );
        });

        refs.connectionList.innerHTML = html.join('');

        var deleteButtons = refs.connectionList.querySelectorAll('.js-delete-connection');
        deleteButtons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                var id = toInt(btn.getAttribute('data-id'), 0);
                if (id > 0) {
                    deleteConnection(id);
                }
            });
        });
    }

    function collectLayoutPayload() {
        var result = [];

        Object.keys(state.positions).forEach(function (id) {
            var pos = state.positions[id];
            result.push({
                id: toInt(id, 0),
                x: toInt(pos.x, 0),
                y: toInt(pos.y, 0)
            });
        });

        return result;
    }

    function saveLayout() {
        var positions = collectLayoutPayload();
        if (!positions.length) {
            showMessage('ไม่พบข้อมูล layout ที่ต้องบันทึก');
            return;
        }

        postJson(endpoints.saveLayout, { positions: positions })
            .then(function (res) {
                if (!res || !res.success) {
                    showMessage((res && res.message) || 'ไม่สามารถบันทึก layout ได้');
                    return;
                }
                setDirty(false);
                showMessage('บันทึก layout เรียบร้อยแล้ว');
                loadData();
            })
            .catch(function () {
                showMessage('ไม่สามารถบันทึก layout ได้');
            });
    }

    function applyPositionsToCards() {
        state.personnel.forEach(function (person) {
            var card = refs.canvas.querySelector('.personnel-card[data-id="' + person.id + '"]');
            var pos = state.positions[person.id];
            if (!card || !pos) {
                return;
            }

            card.style.left = pos.x + 'px';
            card.style.top = pos.y + 'px';
        });

        adjustCanvasSize();
        refreshLinePositions();
        setDirty(true);
    }

    function autoArrange() {
        var sorted = state.personnel.slice().sort(function (a, b) {
            var depA = (a.department_name || '').toLowerCase();
            var depB = (b.department_name || '').toLowerCase();
            if (depA !== depB) {
                return depA < depB ? -1 : 1;
            }

            var sortA = toInt(a.sort_order, 0);
            var sortB = toInt(b.sort_order, 0);
            if (sortA !== sortB) {
                return sortA - sortB;
            }

            return toInt(a.id, 0) - toInt(b.id, 0);
        });

        var xStart = 20;
        var y = 20;
        var spacingX = 250;
        var spacingY = 175;
        var maxCols = 4;

        var currentDepartment = null;
        var col = 0;

        sorted.forEach(function (person) {
            var dep = person.department_name || '';

            if (currentDepartment !== dep) {
                if (currentDepartment !== null) {
                    y += spacingY;
                }
                currentDepartment = dep;
                col = 0;
            }

            var x = xStart + (col * spacingX);
            state.positions[person.id] = {
                x: x,
                y: y
            };

            col++;
            if (col >= maxCols) {
                col = 0;
                y += spacingY;
            }
        });

        applyPositionsToCards();
    }

    function resetLayout() {
        if (!window.confirm('ต้องการรีเซ็ต layout เป็นค่าเริ่มต้นหรือไม่?')) {
            return;
        }

        var xStart = 20;
        var yStart = 20;
        var spacingX = 250;
        var spacingY = 170;

        state.personnel.forEach(function (person, index) {
            state.positions[person.id] = {
                x: xStart + ((index % 4) * spacingX),
                y: yStart + (Math.floor(index / 4) * spacingY)
            };
        });

        applyPositionsToCards();
        saveLayout();
    }


    function bindFormEvents() {
        if (refs.formImageInput) {
            refs.formImageInput.addEventListener('change', function () {
                validateClientImageFile();
            });
        }

        if (refs.form) {
            refs.form.addEventListener('submit', function (event) {
                if (!validateClientImageFile()) {
                    event.preventDefault();
                }
            });
        }
    }
    function bindToolbarEvents() {
        if (refs.btnApplyFilter) {
            refs.btnApplyFilter.addEventListener('click', function () {
                loadData();
            });
        }

        if (refs.btnClearFilter) {
            refs.btnClearFilter.addEventListener('click', function () {
                if (refs.searchInput) {
                    refs.searchInput.value = '';
                }
                if (refs.departmentFilter) {
                    refs.departmentFilter.value = '';
                }
                if (refs.positionFilter) {
                    refs.positionFilter.value = '';
                }
                if (refs.statusFilter) {
                    refs.statusFilter.value = 'all';
                }
                loadData();
            });
        }

        if (refs.searchInput) {
            refs.searchInput.addEventListener('keydown', function (event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    loadData();
                }
            });
        }

        if (refs.btnSaveLayout) {
            refs.btnSaveLayout.addEventListener('click', function () {
                saveLayout();
            });
        }

        if (refs.btnAutoArrange) {
            refs.btnAutoArrange.addEventListener('click', function () {
                autoArrange();
            });
        }

        if (refs.btnResetLayout) {
            refs.btnResetLayout.addEventListener('click', function () {
                resetLayout();
            });
        }

        if (refs.btnConnectMode) {
            refs.btnConnectMode.addEventListener('click', function () {
                state.connectMode = !state.connectMode;
                state.pendingSourceId = null;
                refs.btnConnectMode.classList.toggle('active', state.connectMode);

                var cards = refs.canvas.querySelectorAll('.personnel-card');
                cards.forEach(function (card) {
                    card.classList.remove('link-source');
                });
            });
        }

        if (refs.btnAddPersonnel) {
            refs.btnAddPersonnel.addEventListener('click', function () {
                openAddModal();
            });
        }
    }

    window.addEventListener('resize', function () {
        refreshLinePositions();
    });

    if (refs.canvasWrapper) {
        refs.canvasWrapper.addEventListener('scroll', function () {
            refreshLinePositions();
        });
    }

    bindToolbarEvents();
    bindFormEvents();
    loadData();
})();

