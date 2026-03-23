<?php

require_once __DIR__ . '/../bootstrap.php';

require_admin_login();

$title = 'Personnel Directory';
$success = flash('success');
$error = flash('error');

include __DIR__ . '/_header.php';
?>

<link rel="stylesheet" href="/nurse_srnh/admin/assets/css/personnel.css">

<div id="personnelPage"
     data-load-url="/nurse_srnh/admin/personnel_load.php"
     data-save-layout-url="/nurse_srnh/admin/personnel_save_layout.php"
     data-save-connection-url="/nurse_srnh/admin/personnel_save_connection.php"
     data-delete-connection-url="/nurse_srnh/admin/personnel_delete_connection.php"
     data-save-url="/nurse_srnh/admin/personnel_save.php"
     data-delete-url="/nurse_srnh/admin/personnel_delete.php">

    <div class="d-flex flex-wrap justify-content-between align-items-end mb-3 gap-2">
        <div>
            <h1 class="page-title h3 mb-1">Personnel Directory</h1>
            <p class="text-soft mb-0">Manage personnel records, drag card positions, and line connections inside your organization.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <button class="btn btn-brand" id="btnAddPersonnel" type="button" data-bs-toggle="modal" data-bs-target="#personnelModal">Add Personnel</button>
            <a class="btn btn-outline-secondary" href="/nurse_srnh/admin/departments.php">Manage Departments</a>
            <button class="btn btn-outline-primary" id="btnSaveLayout" type="button">Save Layout</button>
            <button class="btn btn-outline-secondary" id="btnAutoArrange" type="button">Auto Arrange</button>
            <button class="btn btn-outline-danger" id="btnResetLayout" type="button">Reset Layout</button>
            <button class="btn btn-outline-dark" id="btnConnectMode" type="button">Connect Mode</button>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success glass-card border-0"><?= h($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger glass-card border-0"><?= h($error) ?></div>
    <?php endif; ?>

    <div class="alert alert-info glass-card border-0 mb-3">
        <div class="fw-semibold mb-1">Hierarchy guideline: Head -> Department -> Personnel</div>
        <div class="small">
            1) Create a head profile with no Parent.<br>
            2) Create department leads/departments with Parent = head.<br>
            3) Create staff with Parent = department lead and use Sort Order for display order.
        </div>
    </div>

    <div class="glass-card p-3 mb-3">
        <div class="row g-2 align-items-end">
            <div class="col-lg-3">
                <label class="form-label mb-1">Search</label>
                <input type="text" id="searchInput" class="form-control" placeholder="Name / Department / Position">
            </div>
            <div class="col-lg-2">
                <label class="form-label mb-1">Department</label>
                <input type="text" id="departmentFilter" class="form-control" placeholder="Department">
            </div>
            <div class="col-lg-2">
                <label class="form-label mb-1">Position</label>
                <input type="text" id="positionFilter" class="form-control" placeholder="Position">
            </div>
            <div class="col-lg-2">
                <label class="form-label mb-1">Status</label>
                <select id="statusFilter" class="form-select">
                    <option value="all">All</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            <div class="col-lg-3 d-flex gap-2">
                <button class="btn btn-outline-primary w-100" id="btnApplyFilter" type="button">Search/Filter</button>
                <button class="btn btn-outline-secondary" id="btnClearFilter" type="button">Clear</button>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-xl-9">
            <div class="glass-card p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="small text-soft">
                        Drag cards to new positions, then click "Save Layout" to persist changes.
                    </div>
                    <span id="layoutDirtyBadge" class="badge text-bg-warning d-none">Unsaved layout changes</span>
                </div>
                <div id="canvasWrapper">
                    <div id="personnelCanvas"></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3">
            <div class="glass-card p-3 h-100">
                <h2 class="h6 mb-2">Current Connections</h2>
                <p class="small text-soft mb-2">Enable Connect Mode, then click source card and target card.</p>
                <div id="connectionList" class="connection-list"></div>
            </div>
        </div>
    </div>

    <form id="personnelDeleteForm" method="post" action="/nurse_srnh/admin/personnel_delete.php" class="d-none">
        <input type="hidden" name="id" id="deletePersonnelId" value="">
    </form>

    <div class="modal fade" id="personnelModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <form id="personnelForm" method="post" enctype="multipart/form-data" action="/nurse_srnh/admin/personnel_save.php">
                    <div class="modal-header">
                        <h2 class="modal-title fs-5" id="personnelModalLabel">Add Personnel</h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id" id="personnel_id" value="">
                        <input type="hidden" name="existing_profile_image" id="existing_profile_image" value="">

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Full Name <span class="text-danger">*</span></label>
                                <input type="text" name="full_name" id="full_name" class="form-control" maxlength="255" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Position</label>
                                <input type="text" name="position_name" id="position_name" class="form-control" maxlength="255">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Department</label>
                                <input type="text" name="department_name" id="department_name" class="form-control" maxlength="255">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Unit</label>
                                <input type="text" name="unit_name" id="unit_name" class="form-control" maxlength="255">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Phone</label>
                                <input type="text" name="phone" id="phone" class="form-control" maxlength="50">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Internal Phone</label>
                                <input type="text" name="internal_phone" id="internal_phone" class="form-control" maxlength="50">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Sort Order</label>
                                <input type="number" name="sort_order" id="sort_order" class="form-control" value="0">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Status</label>
                                <select name="status" id="status" class="form-select">
                                    <option value="1">Active</option>
                                    <option value="0">Inactive</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Parent</label>
                                <select name="parent_id" id="parent_id" class="form-select">
                                    <option value="">- None -</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Note</label>
                                <textarea name="note" id="note" class="form-control" rows="3" maxlength="4000"></textarea>
                            </div>
                            <div class="col-12">
                                <div class="p-3 rounded personnel-upload-box">
                                    <label class="form-label mb-2">Profile Image (jpg, jpeg, png, gif, webp, max 5MB)</label>
                                    <input class="form-control" type="file" name="profile_image_file" id="profile_image_file" accept=".jpg,.jpeg,.png,.gif,.webp">
                                    <div id="currentImageWrap" class="mt-2 d-none">
                                        <img id="currentImagePreview" src="" alt="preview" class="current-image-preview">
                                        <div class="form-check mt-2">
                                            <input class="form-check-input" type="checkbox" name="remove_profile_image" id="remove_profile_image">
                                            <label class="form-check-label" for="remove_profile_image">Remove current image</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-brand">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/interactjs/dist/interact.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/leader-line-new@1.1.9/leader-line.min.js"></script>
<script src="/nurse_srnh/admin/assets/js/personnel.js?v=20260322d"></script>

<?php include __DIR__ . '/_footer.php'; ?>

