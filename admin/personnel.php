<?php

require_once __DIR__ . '/../bootstrap.php';

require_admin_login();

$title = 'ทำเนียบบุคลากร';
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
            <h1 class="page-title h3 mb-1">ทำเนียบบุคลากร</h1>
            <p class="text-soft mb-0">จัดการรายชื่อบุคลากร จัดตำแหน่งกล่อง และความเชื่อมโยงภายในหน่วยงาน</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <button class="btn btn-brand" id="btnAddPersonnel" type="button" data-bs-toggle="modal" data-bs-target="#personnelModal">เพิ่มบุคลากร</button>
            <button class="btn btn-outline-primary" id="btnSaveLayout" type="button">บันทึก layout</button>
            <button class="btn btn-outline-secondary" id="btnAutoArrange" type="button">Auto Arrange</button>
            <button class="btn btn-outline-danger" id="btnResetLayout" type="button">Reset Layout</button>
            <button class="btn btn-outline-dark" id="btnConnectMode" type="button">โหมดเชื่อมเส้น</button>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success glass-card border-0"><?= h($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger glass-card border-0"><?= h($error) ?></div>
    <?php endif; ?>

    <div class="glass-card p-3 mb-3">
        <div class="row g-2 align-items-end">
            <div class="col-lg-3">
                <label class="form-label mb-1">ค้นหา</label>
                <input type="text" id="searchInput" class="form-control" placeholder="ชื่อ/แผนก/ตำแหน่ง">
            </div>
            <div class="col-lg-2">
                <label class="form-label mb-1">แผนก</label>
                <input type="text" id="departmentFilter" class="form-control" placeholder="ระบุแผนก">
            </div>
            <div class="col-lg-2">
                <label class="form-label mb-1">ตำแหน่ง</label>
                <input type="text" id="positionFilter" class="form-control" placeholder="ระบุตำแหน่ง">
            </div>
            <div class="col-lg-2">
                <label class="form-label mb-1">สถานะ</label>
                <select id="statusFilter" class="form-select">
                    <option value="all">ทั้งหมด</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            <div class="col-lg-3 d-flex gap-2">
                <button class="btn btn-outline-primary w-100" id="btnApplyFilter" type="button">ค้นหา/กรอง</button>
                <button class="btn btn-outline-secondary" id="btnClearFilter" type="button">ล้าง</button>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-xl-9">
            <div class="glass-card p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="small text-soft">
                        ลากการ์ดเพื่อจัดตำแหน่ง แล้วกด "บันทึก layout" เพื่อบันทึกลงฐานข้อมูล
                    </div>
                    <span id="layoutDirtyBadge" class="badge text-bg-warning d-none">มีการเปลี่ยนแปลงที่ยังไม่บันทึก</span>
                </div>
                <div id="canvasWrapper">
                    <div id="personnelCanvas"></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3">
            <div class="glass-card p-3 h-100">
                <h2 class="h6 mb-2">เส้นเชื่อมปัจจุบัน</h2>
                <p class="small text-soft mb-2">เปิดโหมดเชื่อมเส้น แล้วคลิกการ์ดต้นทางและปลายทาง</p>
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
                        <h2 class="modal-title fs-5" id="personnelModalLabel">เพิ่มบุคลากร</h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id" id="personnel_id" value="">
                        <input type="hidden" name="existing_profile_image" id="existing_profile_image" value="">

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">ชื่อ-นามสกุล <span class="text-danger">*</span></label>
                                <input type="text" name="full_name" id="full_name" class="form-control" maxlength="255" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">ตำแหน่ง</label>
                                <input type="text" name="position_name" id="position_name" class="form-control" maxlength="255">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">แผนก</label>
                                <input type="text" name="department_name" id="department_name" class="form-control" maxlength="255">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">หน่วยงาน</label>
                                <input type="text" name="unit_name" id="unit_name" class="form-control" maxlength="255">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">เบอร์โทร</label>
                                <input type="text" name="phone" id="phone" class="form-control" maxlength="50">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">เบอร์ภายใน</label>
                                <input type="text" name="internal_phone" id="internal_phone" class="form-control" maxlength="50">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">ลำดับ</label>
                                <input type="number" name="sort_order" id="sort_order" class="form-control" value="0">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">สถานะ</label>
                                <select name="status" id="status" class="form-select">
                                    <option value="1">Active</option>
                                    <option value="0">Inactive</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">ผู้บังคับบัญชา (Parent)</label>
                                <select name="parent_id" id="parent_id" class="form-select">
                                    <option value="">- ไม่ระบุ -</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">หมายเหตุ</label>
                                <textarea name="note" id="note" class="form-control" rows="3" maxlength="4000"></textarea>
                            </div>
                            <div class="col-12">
                                <div class="p-3 rounded personnel-upload-box">
                                    <label class="form-label mb-2">รูปโปรไฟล์ (jpg, jpeg, png, gif, webp ไม่เกิน 5MB)</label>
                                    <input class="form-control" type="file" name="profile_image_file" id="profile_image_file" accept=".jpg,.jpeg,.png,.gif,.webp">
                                    <div id="currentImageWrap" class="mt-2 d-none">
                                        <img id="currentImagePreview" src="" alt="preview" class="current-image-preview">
                                        <div class="form-check mt-2">
                                            <input class="form-check-input" type="checkbox" name="remove_profile_image" id="remove_profile_image">
                                            <label class="form-check-label" for="remove_profile_image">ลบรูปปัจจุบัน</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="submit" class="btn btn-brand">บันทึกข้อมูล</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/interactjs/dist/interact.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/leader-line-new@1.1.9/leader-line.min.js"></script>
<script src="/nurse_srnh/admin/assets/js/personnel.js"></script>

<?php include __DIR__ . '/_footer.php'; ?>
