
(function () {
    'use strict';

    var root = document.getElementById('orgChartPage');
    if (!root) { return; }

    var endpoints = {
        load: root.getAttribute('data-load-url') || '',
        save: root.getAttribute('data-save-url') || '',
        saveLayout: root.getAttribute('data-save-layout-url') || '',
        staffLoad: root.getAttribute('data-staff-load-url') || ''
    };

    var refs = {
        emptyState: document.getElementById('orgEmptyState'),
        mainSection: document.getElementById('orgMainChartSection'),
        mainCanvas: document.getElementById('orgMainCanvas'),
        deptSections: document.getElementById('orgDepartmentSections'),
        btnSaveLayout: document.getElementById('btnSaveLayout'),
        btnAutoArrange: document.getElementById('btnAutoArrange'),
        btnResetLayout: document.getElementById('btnResetLayout'),
        btnDeleteMainChart: document.getElementById('btnDeleteMainChart'),
        easySelectedInfo: document.getElementById('easySelectedInfo'),
        btnEasyAddChild: document.getElementById('btnEasyAddChild'),
        btnEasyCreateSubChart: document.getElementById('btnEasyCreateSubChart'),
        btnEasyEdit: document.getElementById('btnEasyEdit'),
        btnEasyDelete: document.getElementById('btnEasyDelete'),
        mainForm: document.getElementById('mainChartForm'),
        nodeForm: document.getElementById('nodeForm'),
        nodeModalTitle: document.getElementById('nodeModalTitle'),
        nodeFormAction: document.getElementById('nodeFormAction'),
        nodeId: document.getElementById('nodeId'),
        nodeChartId: document.getElementById('nodeChartId'),
        nodeParentId: document.getElementById('nodeParentId'),
        nodeX: document.getElementById('nodeX'),
        nodeY: document.getElementById('nodeY'),
        nodeFullName: document.getElementById('nodeFullName'),
        nodePersonnelType: document.getElementById('nodePersonnelType'),
        nodePositionName: document.getElementById('nodePositionName'),
        nodeDepartmentName: document.getElementById('nodeDepartmentName'),
        nodeUnitName: document.getElementById('nodeUnitName'),
        nodeStatus: document.getElementById('nodeStatus'),
        nodePhone: document.getElementById('nodePhone'),
        nodeInternalPhone: document.getElementById('nodeInternalPhone'),
        nodeNote: document.getElementById('nodeNote'),
        nodeProfileImage: document.getElementById('nodeProfileImage'),
        nodeStaffProfileImage: document.getElementById('nodeStaffProfileImage'),
        mainProfileImage: document.getElementById('mainProfileImage'),
        mainStaffProfileImage: document.getElementById('mainStaffProfileImage'),
        staffSelect: document.getElementById('staffSelect'),
        btnApplyStaffToNode: document.getElementById('btnApplyStaffToNode'),
        staffSearchInput: document.getElementById('staffSearchInput'),
        btnOpenStaffManager: document.getElementById('btnOpenStaffManager'),
        btnRefreshStaffList: document.getElementById('btnRefreshStaffList'),
        mainStaffSelect: document.getElementById('mainStaffSelect'),
        mainStaffSearchInput: document.getElementById('mainStaffSearchInput'),
        btnApplyStaffToMain: document.getElementById('btnApplyStaffToMain'),
        btnRefreshStaffListMain: document.getElementById('btnRefreshStaffListMain')
    };

    var state = { emptyState: true, mainChartId: 0, charts: {}, dirty: false, staffPool: [], filteredStaffPool: [], mainFilteredStaffPool: [], selectedChartId: 0, selectedNodeId: 0 };
    var bsMainModal = window.bootstrap ? window.bootstrap.Modal.getOrCreateInstance(document.getElementById('mainChartModal')) : null;
    var bsNodeModal = window.bootstrap ? window.bootstrap.Modal.getOrCreateInstance(document.getElementById('nodeModal')) : null;

    function toInt(v, f) { var n = parseInt(v, 10); return Number.isNaN(n) ? (f || 0) : n; }
    function clamp(v) { var n = toInt(v, 0); if (n < 0) return 0; if (n > 50000) return 50000; return n; }
    function esc(v) { var d = document.createElement('div'); d.innerText = v == null ? '' : String(v); return d.innerHTML; }
    function safeImagePath(v) {
        var s = (v == null ? '' : String(v)).trim();
        if (!s) return '';
        s = s.replace(/\\/g, '/');
        s = s.replace(/^https?:\/\/[^/]+\/nurse_srnh\//i, '');
        s = s.replace(/^\/nurse_srnh\//i, '');
        s = s.replace(/^\/+/, '');
        if (s.indexOf('uploads/personnel/') !== 0) return '';
        return s;
    }
    function msg(m) { window.alert(m || 'Error'); }

    function fetchJson(url, opt) {
        return fetch(url, opt || {}).then(function (r) {
            return r.text().then(function (t) {
                var s = (t || '').replace(/^\uFEFF/, '').trim();
                try { return JSON.parse(s); } catch (e) { return { success: false, message: 'Invalid JSON response' }; }
            });
        });
    }

    function postForm(fd) { return fetchJson(endpoints.save, { method: 'POST', body: fd }); }

    function normalizeNode(n) {
        n.id = toInt(n.id, 0);
        n.chart_id = toInt(n.chart_id, 0);
        n.parent_node_id = n.parent_node_id === null ? null : toInt(n.parent_node_id, 0);
        n.x_position = clamp(n.x_position);
        n.y_position = clamp(n.y_position);
        n.level_no = toInt(n.level_no, 0);
        n.status = toInt(n.status, 1);
        return n;
    }

    function buildState(res) {
        state.charts = {};
        state.mainChartId = 0;

        if (!res || !res.success || res.empty_state || !res.data || !res.data.main_chart) {
            state.emptyState = true;
            return;
        }

        state.emptyState = false;
        var data = res.data;
        var main = data.main_chart;
        main.id = toInt(main.id, 0);
        state.mainChartId = main.id;
        state.charts[main.id] = {
            chart: main,
            isMain: true,
            nodes: (data.main_nodes || []).map(normalizeNode),
            connections: data.main_connections || []
        };

        (data.department_charts || []).forEach(function (d) {
            if (!d || !d.chart) return;
            d.chart.id = toInt(d.chart.id, 0);
            state.charts[d.chart.id] = {
                chart: d.chart,
                isMain: false,
                nodes: (d.nodes || []).map(normalizeNode),
                connections: d.connections || []
            };
        });
    }

    function chartById(id) { return state.charts[toInt(id, 0)] || null; }
    function findNode(chartId, nodeId) {
        var c = chartById(chartId); if (!c) return null;
        var nid = toInt(nodeId, 0);
        for (var i = 0; i < c.nodes.length; i++) if (toInt(c.nodes[i].id, 0) === nid) return c.nodes[i];
        return null;
    }

    function rootNode(c) {
        var rid = toInt(c.chart.root_node_id, 0);
        if (rid > 0) return findNode(c.chart.id, rid);
        for (var i = 0; i < c.nodes.length; i++) if (c.nodes[i].parent_node_id === null || toInt(c.nodes[i].level_no, 0) === 0) return c.nodes[i];
        return c.nodes.length ? c.nodes[0] : null;
    }

    function updateEasyPanel() {
        var chartId = toInt(state.selectedChartId, 0);
        var nodeId = toInt(state.selectedNodeId, 0);
        var n = (chartId > 0 && nodeId > 0) ? findNode(chartId, nodeId) : null;
        var c = (chartId > 0) ? chartById(chartId) : null;
        var canUse = !!n;
        var canSub = !!(n && c && c.isMain && String(n.personnel_type || '') === 'department_head');

        if (refs.easySelectedInfo) {
            refs.easySelectedInfo.textContent = canUse
                ? ('เลือกแล้ว: ' + (n.full_name || ('#' + n.id)) + ' (' + (n.position_name || '-') + ')')
                : 'โหมดง่าย: คลิกเลือกการ์ดก่อน แล้วใช้ปุ่มด้านขวา';
        }
        if (refs.btnEasyAddChild) refs.btnEasyAddChild.disabled = !canUse;
        if (refs.btnEasyEdit) refs.btnEasyEdit.disabled = !canUse;
        if (refs.btnEasyDelete) refs.btnEasyDelete.disabled = !canUse;
        if (refs.btnEasyCreateSubChart) refs.btnEasyCreateSubChart.disabled = !canSub;

        document.querySelectorAll('.org-node-card').forEach(function (el) { el.classList.remove('node-selected'); });
        if (canUse) {
            var selectedEl = document.querySelector('.org-node-card[data-chart-id="' + chartId + '"][data-node-id="' + nodeId + '"]');
            if (selectedEl) selectedEl.classList.add('node-selected');
        }
    }

    function setSelectedNode(chartId, nodeId) {
        state.selectedChartId = toInt(chartId, 0);
        state.selectedNodeId = toInt(nodeId, 0);
        updateEasyPanel();
    }

    function nodeCard(c, n) {
        var subBtn = '';
        var imagePath = safeImagePath(n.profile_image);
        var dept = (n.department_name || '').trim();
        var unit = (n.unit_name || '').trim();
        var orgLine = '';

        if (dept && unit) {
            orgLine = dept + ' / ' + unit;
        } else if (dept) {
            orgLine = dept;
        } else if (unit) {
            orgLine = unit;
        }

        if (c.isMain && String(n.personnel_type || '') === 'department_head') {
            subBtn = '<button type="button" class="btn btn-sm btn-outline-primary js-sub" data-chart-id="' + c.chart.id + '" data-node-id="' + n.id + '">+ Sub Chart</button>';
        }
        return [
            '<div class="org-node-card" data-chart-id="', c.chart.id, '" data-node-id="', n.id, '" style="left:', clamp(n.x_position), 'px;top:', clamp(n.y_position), 'px;">',
            '<div class="org-node-head"><div class="org-avatar">', (imagePath ? '<img src="/nurse_srnh/' + esc(imagePath) + '" alt="profile">' : '<div class="org-avatar-placeholder">' + esc((n.full_name || '?').charAt(0)) + '</div>'), '</div>',
            '<div class="flex-grow-1 min-w-0"><div class="org-node-name">', esc(n.full_name || ('#' + n.id)), '</div><div class="org-node-position">', esc(n.position_name || '-'), '</div>', (orgLine ? '<div class="org-node-unit">' + esc(orgLine) + '</div>' : ''), '</div></div>',
            '<div class="org-node-actions"><div class="d-flex flex-wrap gap-1">',
            '<button type="button" class="btn btn-sm btn-outline-success js-add" data-chart-id="', c.chart.id, '" data-node-id="', n.id, '">+ Child</button>',
            subBtn,
            '<button type="button" class="btn btn-sm btn-outline-secondary js-edit" data-chart-id="', c.chart.id, '" data-node-id="', n.id, '">Edit</button>',
            '<button type="button" class="btn btn-sm btn-outline-danger js-del" data-chart-id="', c.chart.id, '" data-node-id="', n.id, '">Delete</button>',
            '</div></div></div>'
        ].join('');
    }

    function ensureSize(canvas, c) {
        var w = 1200, h = 540, nw = window.innerWidth < 992 ? 250 : 280;
        c.nodes.forEach(function (n) {
            w = Math.max(w, clamp(n.x_position) + nw + 80);
            h = Math.max(h, clamp(n.y_position) + 230);
        });
        canvas.style.width = w + 'px';
        canvas.style.minHeight = h + 'px';
        return { w: w, h: h, nw: nw, nh: 156 };
    }

    function drawLines(canvas, c) {
        var old = canvas.querySelector('svg.org-lines');
        if (old) old.remove();

        var s = ensureSize(canvas, c);
        var ns = 'http://www.w3.org/2000/svg';
        var svg = document.createElementNS(ns, 'svg');
        svg.setAttribute('class', 'org-lines');
        svg.setAttribute('width', String(s.w));
        svg.setAttribute('height', String(s.h));

        c.connections.forEach(function (ln) {
            var src = findNode(c.chart.id, ln.source_node_id);
            var dst = findNode(c.chart.id, ln.target_node_id);
            if (!src || !dst) return;

            var sx = clamp(src.x_position) + Math.round(s.nw / 2);
            var sy = clamp(src.y_position) + s.nh;
            var tx = clamp(dst.x_position) + Math.round(s.nw / 2);
            var ty = clamp(dst.y_position);
            var my = Math.round((sy + ty) / 2);

            var path = document.createElementNS(ns, 'path');
            path.setAttribute('d', 'M ' + sx + ' ' + sy + ' L ' + sx + ' ' + my + ' L ' + tx + ' ' + my + ' L ' + tx + ' ' + ty);
            path.setAttribute('fill', 'none');
            path.setAttribute('stroke', '#75a6b3');
            path.setAttribute('stroke-width', '2');
            if (String(ln.line_style || '') === 'dashed') path.setAttribute('stroke-dasharray', '6 4');
            svg.appendChild(path);
        });

        canvas.insertBefore(svg, canvas.firstChild);
    }

    function openCreateNode(chartId, parentId, preferredType) {
        if (!refs.nodeForm) {
            msg('Node form not found');
            return;
        }
        refs.nodeForm.reset();
        refs.nodeModalTitle.textContent = 'Add Node';
        refs.nodeFormAction.value = 'create_node';
        refs.nodeId.value = '';
        refs.nodeChartId.value = String(chartId);
        refs.nodeParentId.value = parentId ? String(parentId) : '';

        var p = parentId ? findNode(chartId, parentId) : null;
        refs.nodeDepartmentName.value = p ? (p.department_name || '') : '';
        refs.nodeUnitName.value = p ? (p.unit_name || '') : '';
        refs.nodeX.value = String(p ? clamp(p.x_position + 60) : 80);
        refs.nodeY.value = String(p ? clamp(p.y_position + 220) : 80);
        if (refs.nodePersonnelType) refs.nodePersonnelType.value = preferredType || 'staff';
        if (refs.nodeStaffProfileImage) refs.nodeStaffProfileImage.value = '';

        if (refs.staffSearchInput) refs.staffSearchInput.value = '';
        if (refs.staffSelect) refs.staffSelect.value = '';
        filterStaffOptions('');
        if (bsNodeModal) {
            bsNodeModal.show();
        } else {
            var modalEl = document.getElementById('nodeModal');
            if (modalEl) {
                modalEl.classList.add('show');
                modalEl.style.display = 'block';
                modalEl.removeAttribute('aria-hidden');
            }
        }
    }

    function openEditNode(chartId, nodeId) {
        if (!refs.nodeForm) {
            msg('Node form not found');
            return;
        }
        var n = findNode(chartId, nodeId);
        if (!n) return;

        refs.nodeForm.reset();
        refs.nodeModalTitle.textContent = 'Edit Node';
        refs.nodeFormAction.value = 'update_node';
        refs.nodeId.value = String(n.id);
        refs.nodeChartId.value = String(chartId);
        refs.nodeParentId.value = n.parent_node_id === null ? '' : String(n.parent_node_id);
        refs.nodeX.value = String(clamp(n.x_position));
        refs.nodeY.value = String(clamp(n.y_position));
        refs.nodeFullName.value = n.full_name || '';
        refs.nodePersonnelType.value = n.personnel_type || 'staff';
        refs.nodePositionName.value = n.position_name || '';
        refs.nodeDepartmentName.value = n.department_name || '';
        refs.nodeUnitName.value = n.unit_name || '';
        refs.nodeStatus.value = String(toInt(n.status, 1));
        refs.nodePhone.value = n.phone || '';
        refs.nodeInternalPhone.value = n.internal_phone || '';
        refs.nodeNote.value = n.note || '';
        if (refs.nodeStaffProfileImage) refs.nodeStaffProfileImage.value = safeImagePath(n.profile_image || '');

        if (refs.staffSearchInput) refs.staffSearchInput.value = '';
        if (refs.staffSelect) refs.staffSelect.value = '';
        filterStaffOptions('');
        if (bsNodeModal) {
            bsNodeModal.show();
        } else {
            var modalEl = document.getElementById('nodeModal');
            if (modalEl) {
                modalEl.classList.add('show');
                modalEl.style.display = 'block';
                modalEl.removeAttribute('aria-hidden');
            }
        }
    }

    
    function renderStaffSelectOptions(list) {
        if (!refs.staffSelect) return;

        var src = Array.isArray(list) ? list : state.staffPool;
        var html = ['<option value="">- เลือกเจ้าหน้าที่ -</option>'];

        src.forEach(function (p) {
            var label = (p.full_name || ('#' + p.id)) + ' | ' + (p.position_name || '-') + ' | ' + (p.department_name || '-');
            html.push('<option value="' + p.id + '">' + esc(label) + '</option>');
        });

        refs.staffSelect.innerHTML = html.join('');
    }


    function renderMainStaffSelectOptions(list) {
        if (!refs.mainStaffSelect) return;

        var src = Array.isArray(list) ? list : state.staffPool;
        var html = ['<option value="">- เลือกเจ้าหน้าที่ -</option>'];

        src.forEach(function (p) {
            var label = (p.full_name || ('#' + p.id)) + ' | ' + (p.position_name || '-') + ' | ' + (p.department_name || '-');
            html.push('<option value="' + p.id + '">' + esc(label) + '</option>');
        });

        refs.mainStaffSelect.innerHTML = html.join('');
    }

    function filterMainStaffOptions(keyword) {
        var q = (keyword || '').toLowerCase().trim();
        if (q === '') {
            state.mainFilteredStaffPool = state.staffPool.slice();
            renderMainStaffSelectOptions(state.mainFilteredStaffPool);
            return;
        }

        state.mainFilteredStaffPool = state.staffPool.filter(function (p) {
            var text = [p.full_name, p.position_name, p.department_name, p.unit_name].join(' ').toLowerCase();
            return text.indexOf(q) >= 0;
        });

        renderMainStaffSelectOptions(state.mainFilteredStaffPool);
    }

    function applySelectedStaffToMainForm() {
        if (!refs.mainStaffSelect) return;
        var id = toInt(refs.mainStaffSelect.value, 0);
        if (id <= 0) { msg('กรุณาเลือกเจ้าหน้าที่'); return; }

        var found = null;
        for (var i = 0; i < state.staffPool.length; i++) {
            if (toInt(state.staffPool[i].id, 0) === id) { found = state.staffPool[i]; break; }
        }
        if (!found) { msg('ไม่พบข้อมูลเจ้าหน้าที่'); return; }

        var mainFullName = document.getElementById('mainFullName');
        var mainPositionName = document.getElementById('mainPositionName');
        var mainDepartmentName = document.getElementById('mainDepartmentName');
        var mainUnitName = document.getElementById('mainUnitName');
        var mainPhone = document.getElementById('mainPhone');
        var mainInternalPhone = document.getElementById('mainInternalPhone');
        var mainNote = document.getElementById('mainNote');

        if (mainFullName) mainFullName.value = found.full_name || '';
        if (mainPositionName) mainPositionName.value = found.position_name || '';
        if (mainDepartmentName) mainDepartmentName.value = found.department_name || '';
        if (mainUnitName) mainUnitName.value = found.unit_name || '';
        if (mainPhone) mainPhone.value = found.phone || '';
        if (mainInternalPhone) mainInternalPhone.value = found.internal_phone || '';
        if (mainNote) mainNote.value = found.note || '';
        if (refs.mainStaffProfileImage) refs.mainStaffProfileImage.value = safeImagePath(found.profile_image || '');
    }
    function filterStaffOptions(keyword) {
        var q = (keyword || '').toLowerCase().trim();
        if (q === '') {
            state.filteredStaffPool = state.staffPool.slice();
            renderStaffSelectOptions(state.filteredStaffPool);
            return;
        }

        state.filteredStaffPool = state.staffPool.filter(function (p) {
            var text = [p.full_name, p.position_name, p.department_name, p.unit_name].join(' ').toLowerCase();
            return text.indexOf(q) >= 0;
        });

        renderStaffSelectOptions(state.filteredStaffPool);
    }

    function loadStaffPool(showFeedback) {
        if (!endpoints.staffLoad) return Promise.resolve();
        return fetchJson(endpoints.staffLoad + '?status=active').then(function (res) {
            if (!res || !res.success) {
                if (showFeedback) msg((res && res.message) || 'โหลดรายชื่อเจ้าหน้าที่ไม่สำเร็จ');
                return;
            }
            state.staffPool = Array.isArray(res.personnel) ? res.personnel : [];
            state.filteredStaffPool = state.staffPool.slice();
            state.mainFilteredStaffPool = state.staffPool.slice();
            renderStaffSelectOptions(state.filteredStaffPool);
            renderMainStaffSelectOptions(state.mainFilteredStaffPool);
            if (showFeedback) { msg('รีเฟรชรายชื่อเจ้าหน้าที่แล้ว'); }
        }).catch(function () {
            if (showFeedback) msg('โหลดรายชื่อเจ้าหน้าที่ไม่สำเร็จ');
        });
    }

    function applySelectedStaffToNodeForm() {
        if (!refs.staffSelect) return;
        var id = toInt(refs.staffSelect.value, 0);
        if (id <= 0) { msg('กรุณาเลือกเจ้าหน้าที่'); return; }

        var found = null;
        for (var i = 0; i < state.staffPool.length; i++) {
            if (toInt(state.staffPool[i].id, 0) === id) { found = state.staffPool[i]; break; }
        }
        if (!found) { msg('ไม่พบข้อมูลเจ้าหน้าที่'); return; }

        refs.nodeFullName.value = found.full_name || '';
        refs.nodePositionName.value = found.position_name || '';
        refs.nodeDepartmentName.value = found.department_name || '';
        refs.nodeUnitName.value = found.unit_name || '';
        refs.nodePhone.value = found.phone || '';
        refs.nodeInternalPhone.value = found.internal_phone || '';
        refs.nodeNote.value = found.note || '';
        refs.nodeStatus.value = String(toInt(found.status, 1));
        if (refs.nodeStaffProfileImage) refs.nodeStaffProfileImage.value = safeImagePath(found.profile_image || '');

        if (refs.nodePersonnelType && refs.nodePersonnelType.value === 'staff') {
            if ((found.position_name || '').toLowerCase().indexOf('head') >= 0 || (found.position_name || '').indexOf('หัวหน้า') >= 0) {
                refs.nodePersonnelType.value = 'department_head';
            }
        }
    }

    function isDuplicateNameInChart(chartId, fullName, positionName, excludeNodeId) {
        var c = chartById(chartId);
        if (!c) return false;

        var name = (fullName || '').trim().toLowerCase();
        var position = (positionName || '').trim().toLowerCase();
        var exId = toInt(excludeNodeId, 0);
        if (name === '') return false;

        for (var i = 0; i < c.nodes.length; i++) {
            var node = c.nodes[i];
            if (toInt(node.id, 0) === exId) continue;
            if ((node.full_name || '').trim().toLowerCase() === name && (node.position_name || '').trim().toLowerCase() === position) {
                return true;
            }
        }
        return false;
    }
    function validImage(input) {
        if (!input || !input.files || !input.files.length) return true;
        var f = input.files[0];
        var ext = (f.name || '').split('.').pop().toLowerCase();
        var okExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        var okMime = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

        if (f.size > (5 * 1024 * 1024)) { msg('Image size must be <= 5MB'); input.value = ''; return false; }
        if (okExt.indexOf(ext) < 0 && okMime.indexOf(f.type) < 0) { msg('Invalid image type'); input.value = ''; return false; }
        return true;
    }

    function initDrag(canvas, c) {
        if (typeof window.interact === 'undefined') return;
        var selector = '.org-node-card[data-chart-id="' + c.chart.id + '"]';
        try { window.interact(selector).unset(); } catch (e) {}

        window.interact(selector).draggable({
            allowFrom: '.org-node-head, .org-node-head *',
            ignoreFrom: '.org-node-actions, .org-node-actions *, button, a, input, select, textarea',
            listeners: {
                move: function (ev) {
                    var el = ev.target;
                    var nodeId = toInt(el.getAttribute('data-node-id'), 0);
                    var n = findNode(c.chart.id, nodeId);
                    if (!n) return;

                    var x = clamp(toInt(el.style.left, 0) + ev.dx);
                    var y = clamp(toInt(el.style.top, 0) + ev.dy);
                    el.style.left = x + 'px';
                    el.style.top = y + 'px';
                    n.x_position = x;
                    n.y_position = y;
                    state.dirty = true;
                    drawLines(canvas, c);
                }
            }
        });
    }

    function deleteNodeAction(chartId, nodeId) {
        var n = findNode(chartId, nodeId);
        if (!n) return;
        var mode = window.confirm('Delete this node and descendants? Click Cancel to reparent children.') ? 'subtree' : 'reparent';
        if (!window.confirm('Confirm delete: ' + (n.full_name || ('#' + n.id)) + ' ?')) return;

        var fd = new FormData();
        fd.append('action', 'delete_node');
        fd.append('node_id', String(nodeId));
        fd.append('delete_mode', mode);
        postForm(fd).then(function (res) {
            if (!res || !res.success) { msg((res && res.message) || 'Delete failed'); return; }
            setSelectedNode(0, 0);
            loadAll();
        }).catch(function () { msg('Delete failed'); });
    }

    function createSubChartAction(mainNodeId) {
        var fd = new FormData();
        fd.append('action', 'create_department_chart');
        fd.append('main_chart_id', String(state.mainChartId));
        fd.append('main_node_id', String(toInt(mainNodeId, 0)));
        postForm(fd).then(function (res) {
            if (!res || !res.success) { msg((res && res.message) || 'Create sub chart failed'); return; }
            loadAll();
        }).catch(function () { msg('Create sub chart failed'); });
    }

    function bindActions(canvas, c) {
        canvas.addEventListener('click', function (e) {
            if (!e.target.closest('.org-node-card')) {
                setSelectedNode(0, 0);
            }
        });

        canvas.querySelectorAll('.org-node-card').forEach(function (cardEl) {
            cardEl.addEventListener('click', function () {
                setSelectedNode(toInt(cardEl.getAttribute('data-chart-id'), 0), toInt(cardEl.getAttribute('data-node-id'), 0));
            });
        });

        canvas.querySelectorAll('.org-node-actions .btn').forEach(function (btn) {
            ['pointerdown', 'mousedown', 'touchstart'].forEach(function (evtName) {
                btn.addEventListener(evtName, function (e) {
                    e.stopPropagation();
                });
            });
        });

        canvas.querySelectorAll('.js-add').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                openCreateNode(toInt(btn.getAttribute('data-chart-id'), 0), toInt(btn.getAttribute('data-node-id'), 0), 'staff');
            });
        });

        canvas.querySelectorAll('.js-edit').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                openEditNode(toInt(btn.getAttribute('data-chart-id'), 0), toInt(btn.getAttribute('data-node-id'), 0));
            });
        });

        canvas.querySelectorAll('.js-del').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                deleteNodeAction(toInt(btn.getAttribute('data-chart-id'), 0), toInt(btn.getAttribute('data-node-id'), 0));
            });
        });

        canvas.querySelectorAll('.js-sub').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                createSubChartAction(toInt(btn.getAttribute('data-node-id'), 0));
            });
        });
    }

    function renderChart(canvas, c) {
        canvas.innerHTML = c.nodes.map(function (n) { return nodeCard(c, n); }).join('');
        bindActions(canvas, c);
        initDrag(canvas, c);
        drawLines(canvas, c);
    }

    function deptSection(c) {
        var title = c.chart.chart_name || ('Department #' + c.chart.id);
        return [
            '<div class="glass-card p-3 org-department-section" data-chart-id="', c.chart.id, '">',
            '<div class="d-flex justify-content-between align-items-center mb-2">',
            '<div><div class="fw-semibold">', esc(title), '</div><div class="small text-soft">', esc(c.chart.department_name || 'Department chart'), '</div></div>',
            '<div class="d-flex gap-1"><button type="button" class="btn btn-sm btn-outline-success js-add-root" data-chart-id="', c.chart.id, '">+ Add Member</button>',
            '<button type="button" class="btn btn-sm btn-outline-danger js-del-chart" data-chart-id="', c.chart.id, '">Delete Chart</button></div></div>',
            '<div class="org-canvas-wrap p-2"><div id="deptCanvas_', c.chart.id, '" class="org-canvas"></div></div></div>'
        ].join('');
    }

    function renderAll() {
        if (state.emptyState) {
            refs.emptyState.classList.remove('d-none');
            refs.mainSection.classList.add('d-none');
            refs.deptSections.innerHTML = '';
            setSelectedNode(0, 0);
            return;
        }

        refs.emptyState.classList.add('d-none');
        refs.mainSection.classList.remove('d-none');

        var main = chartById(state.mainChartId);
        if (main && refs.mainCanvas) renderChart(refs.mainCanvas, main);

        var deps = [];
        Object.keys(state.charts).forEach(function (id) { if (!state.charts[id].isMain) deps.push(state.charts[id]); });
        deps.sort(function (a, b) { return toInt(a.chart.sort_order, 0) - toInt(b.chart.sort_order, 0) || toInt(a.chart.id, 0) - toInt(b.chart.id, 0); });

        refs.deptSections.innerHTML = deps.map(deptSection).join('');

        deps.forEach(function (c) {
            var canvas = document.getElementById('deptCanvas_' + c.chart.id);
            if (canvas) renderChart(canvas, c);
        });

        refs.deptSections.querySelectorAll('.js-add-root').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var c = chartById(toInt(btn.getAttribute('data-chart-id'), 0));
                if (!c || !c.nodes.length) return;
                var r = rootNode(c) || c.nodes[0];
                openCreateNode(c.chart.id, r.id);
            });
        });

        refs.deptSections.querySelectorAll('.js-del-chart').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var chartId = toInt(btn.getAttribute('data-chart-id'), 0);
                if (!window.confirm('Delete this department chart?')) return;
                var fd = new FormData();
                fd.append('action', 'delete_chart');
                fd.append('chart_id', String(chartId));
                postForm(fd).then(function (res) {
                    if (!res || !res.success) { msg((res && res.message) || 'Delete chart failed'); return; }
                    loadAll();
                }).catch(function () { msg('Delete chart failed'); });
            });
        });

        if (!findNode(state.selectedChartId, state.selectedNodeId)) {
            setSelectedNode(0, 0);
        } else {
            updateEasyPanel();
        }
    }

    function saveLayout() {
        var items = [];
        Object.keys(state.charts).forEach(function (id) {
            state.charts[id].nodes.forEach(function (n) {
                items.push({ node_id: toInt(n.id, 0), x_position: clamp(n.x_position), y_position: clamp(n.y_position) });
            });
        });

        if (!items.length) { msg('No positions to save'); return; }

        fetchJson(endpoints.saveLayout, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ items: items })
        }).then(function (res) {
            if (!res || !res.success) { msg((res && res.message) || 'Save layout failed'); return; }
            state.dirty = false;
            msg('Layout saved');
        }).catch(function () { msg('Save layout failed'); });
    }

    function autoArrangeChart(c) {
        var nodes = c.nodes;
        if (!nodes.length) return;

        var map = {};
        nodes.forEach(function (n) {
            var key = n.parent_node_id === null ? 'root' : String(n.parent_node_id);
            if (!map[key]) map[key] = [];
            map[key].push(n);
        });

        var root = rootNode(c) || nodes[0];
        var q = [{ node: root, depth: 0 }], levels = {}, seen = {};

        while (q.length) {
            var cur = q.shift();
            if (seen[cur.node.id]) continue;
            seen[cur.node.id] = true;
            if (!levels[cur.depth]) levels[cur.depth] = [];
            levels[cur.depth].push(cur.node);
            (map[String(cur.node.id)] || []).forEach(function (child) { q.push({ node: child, depth: cur.depth + 1 }); });
        }

        var nw = window.innerWidth < 992 ? 250 : 280;
        var sx = nw + 60;
        var sy = 220;

        Object.keys(levels).forEach(function (k) {
            var d = toInt(k, 0);
            var row = levels[d];
            var start = Math.max(24, Math.round((1200 - (row.length * sx)) / 2));
            row.forEach(function (n, i) {
                n.x_position = clamp(start + (i * sx));
                n.y_position = clamp(40 + (d * sy));
            });
        });
    }

    function resetGrid(c) {
        var nw = window.innerWidth < 992 ? 250 : 280;
        var sx = nw + 40;
        var sy = 190;
        c.nodes.forEach(function (n, i) {
            n.x_position = clamp(24 + ((i % 4) * sx));
            n.y_position = clamp(30 + (Math.floor(i / 4) * sy));
        });
    }

    function loadAll() {
        fetchJson(endpoints.load).then(function (res) {
            if (!res || !res.success) { msg((res && res.message) || 'Load failed'); return; }
            buildState(res);
            renderAll();
        }).catch(function () { msg('Load failed'); });
    }

    if (refs.btnSaveLayout) refs.btnSaveLayout.addEventListener('click', saveLayout);
    if (refs.btnAutoArrange) refs.btnAutoArrange.addEventListener('click', function () {
        Object.keys(state.charts).forEach(function (id) { autoArrangeChart(state.charts[id]); });
        state.dirty = true;
        renderAll();
    });
    if (refs.btnResetLayout) refs.btnResetLayout.addEventListener('click', function () {
        if (!window.confirm('Reset all node positions?')) return;
        Object.keys(state.charts).forEach(function (id) { resetGrid(state.charts[id]); });
        state.dirty = true;
        renderAll();
    });
    if (refs.btnDeleteMainChart) refs.btnDeleteMainChart.addEventListener('click', function () {
        if (!state.mainChartId || !window.confirm('Delete main chart and all sub charts?')) return;
        var fd = new FormData();
        fd.append('action', 'delete_chart');
        fd.append('chart_id', String(state.mainChartId));
        fd.append('delete_mode', 'cascade');
        postForm(fd).then(function (res) {
            if (!res || !res.success) { msg((res && res.message) || 'Delete main chart failed'); return; }
            loadAll();
        }).catch(function () { msg('Delete main chart failed'); });
    });

    if (refs.btnEasyAddChild) refs.btnEasyAddChild.addEventListener('click', function () {
        var chartId = toInt(state.selectedChartId, 0);
        var nodeId = toInt(state.selectedNodeId, 0);
        if (chartId <= 0 || nodeId <= 0) { msg('กรุณาคลิกเลือกการ์ดก่อน'); return; }
        openCreateNode(chartId, nodeId, 'staff');
    });

    if (refs.btnEasyEdit) refs.btnEasyEdit.addEventListener('click', function () {
        var chartId = toInt(state.selectedChartId, 0);
        var nodeId = toInt(state.selectedNodeId, 0);
        if (chartId <= 0 || nodeId <= 0) { msg('กรุณาคลิกเลือกการ์ดก่อน'); return; }
        openEditNode(chartId, nodeId);
    });

    if (refs.btnEasyDelete) refs.btnEasyDelete.addEventListener('click', function () {
        var chartId = toInt(state.selectedChartId, 0);
        var nodeId = toInt(state.selectedNodeId, 0);
        if (chartId <= 0 || nodeId <= 0) { msg('กรุณาคลิกเลือกการ์ดก่อน'); return; }
        deleteNodeAction(chartId, nodeId);
    });

    if (refs.btnEasyCreateSubChart) refs.btnEasyCreateSubChart.addEventListener('click', function () {
        var chartId = toInt(state.selectedChartId, 0);
        var nodeId = toInt(state.selectedNodeId, 0);
        var c = chartById(chartId);
        var n = findNode(chartId, nodeId);
        if (!c || !n) { msg('กรุณาคลิกเลือกการ์ดก่อน'); return; }
        if (!c.isMain || String(n.personnel_type || '') !== 'department_head') {
            msg('สร้างผังย่อยได้เฉพาะหัวหน้าแผนกในผังหลัก');
            return;
        }
        createSubChartAction(nodeId);
    });

    if (refs.mainForm) refs.mainForm.addEventListener('submit', function (e) {
        e.preventDefault();
        var img = refs.mainProfileImage || refs.mainForm.querySelector('input[name="profile_image"]');
        if (!validImage(img)) return;
        var fd = new FormData(refs.mainForm);
        postForm(fd).then(function (res) {
            if (!res || !res.success) { msg((res && res.message) || 'Create main chart failed'); return; }
            if (res.already_exists) { msg('มีผังหลักอยู่แล้ว ระบบจะเปิดผังเดิมให้'); }
            refs.mainForm.reset();
            if (refs.mainStaffSearchInput) refs.mainStaffSearchInput.value = '';
            if (refs.mainStaffSelect) refs.mainStaffSelect.value = '';
            if (refs.mainStaffProfileImage) refs.mainStaffProfileImage.value = '';
            filterMainStaffOptions('');
            if (bsMainModal) bsMainModal.hide();
            loadAll();
        }).catch(function () { msg('Create main chart failed'); });
    });

    if (refs.nodeForm) refs.nodeForm.addEventListener('submit', function (e) {
        e.preventDefault();
        if (!validImage(refs.nodeProfileImage)) return;

        var action = refs.nodeFormAction ? refs.nodeFormAction.value : 'create_node';
        var chartId = toInt(refs.nodeChartId ? refs.nodeChartId.value : 0, 0);
        var nodeId = toInt(refs.nodeId ? refs.nodeId.value : 0, 0);
        var fullName = refs.nodeFullName ? refs.nodeFullName.value : '';
        var positionName = refs.nodePositionName ? refs.nodePositionName.value : '';

        if (isDuplicateNameInChart(chartId, fullName, positionName, action === 'update_node' ? nodeId : 0)) {
            msg('มีชื่อและตำแหน่งนี้ในผังเดียวกันแล้ว กรุณาตรวจสอบข้อมูลก่อนบันทึก');
            return;
        }

        var fd = new FormData(refs.nodeForm);
        postForm(fd).then(function (res) {
            if (!res || !res.success) { msg((res && res.message) || 'Save node failed'); return; }
            if (bsNodeModal) bsNodeModal.hide();
            loadAll();
        }).catch(function () { msg('Save node failed'); });
    });

    window.addEventListener('beforeunload', function (e) {
        if (!state.dirty) return;
        e.preventDefault();
        e.returnValue = '';
    });

    window.addEventListener('resize', function () { if (!state.emptyState) renderAll(); });
    if (refs.btnApplyStaffToNode) {
        refs.btnApplyStaffToNode.addEventListener('click', function () {
            applySelectedStaffToNodeForm();
        });
    }

    if (refs.staffSelect) {
        refs.staffSelect.addEventListener('change', function () {
            if (refs.staffSelect.value) {
                applySelectedStaffToNodeForm();
            } else if (refs.nodeStaffProfileImage) {
                refs.nodeStaffProfileImage.value = '';
            }
        });
    }

    if (refs.staffSearchInput) {
        refs.staffSearchInput.addEventListener('input', function () {
            filterStaffOptions(refs.staffSearchInput.value || '');
        });
    }

    if (refs.mainStaffSearchInput) {
        refs.mainStaffSearchInput.addEventListener('input', function () {
            filterMainStaffOptions(refs.mainStaffSearchInput.value || '');
        });
    }

    if (refs.btnApplyStaffToMain) {
        refs.btnApplyStaffToMain.addEventListener('click', function () {
            applySelectedStaffToMainForm();
        });
    }

    if (refs.mainStaffSelect) {
        refs.mainStaffSelect.addEventListener('change', function () {
            if (refs.mainStaffSelect.value) {
                applySelectedStaffToMainForm();
            } else if (refs.mainStaffProfileImage) {
                refs.mainStaffProfileImage.value = '';
            }
        });
    }

    if (refs.btnRefreshStaffList) {
        refs.btnRefreshStaffList.addEventListener('click', function () {
            loadStaffPool(true);
        });
    }

    if (refs.btnRefreshStaffListMain) {
        refs.btnRefreshStaffListMain.addEventListener('click', function () {
            loadStaffPool(true);
        });
    }

    updateEasyPanel();
    loadStaffPool(false);

    loadAll();
})();

























