<?php
require_once __DIR__ . '/../bootstrap.php';

require_admin_login();

$title = 'Organization Chart';
$success = flash('success');
$error = flash('error');

include __DIR__ . '/_header.php';
?>

<style>
#orgChartPage .org-toolbar {
    border: 1px solid rgba(255,255,255,.6);
    border-radius: 16px;
    background: rgba(255,255,255,.9);
    box-shadow: 0 10px 24px rgba(8,33,41,.08);
    padding: 1rem;
}

#orgChartPage .org-empty-state {
    min-height: 56vh;
    display: flex;
    align-items: center;
    justify-content: center;
}

#orgChartPage .org-empty-card {
    width: 100%;
    max-width: 720px;
    border: 1px dashed #9fc4c0;
    border-radius: 20px;
    background: linear-gradient(180deg, #ffffff 0%, #f4fbfa 100%);
    text-align: center;
    padding: 2.2rem 1.4rem;
    box-shadow: 0 12px 30px rgba(8,33,41,.08);
}

#orgChartPage .org-empty-icon {
    width: 86px;
    height: 86px;
    border-radius: 50%;
    margin: 0 auto 1rem;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #e8f5f2;
    color: #0b5d5b;
    font-size: 2rem;
    border: 1px solid #cfe8e2;
}

#orgChartPage .org-empty-title {
    font-size: 1.65rem;
    font-weight: 700;
    margin-bottom: .5rem;
}

#orgChartPage .org-empty-subtitle {
    color: #607382;
    margin-bottom: 1.2rem;
}

#orgChartPage .org-shell {
    min-height: 380px;
}

#orgChartPage .org-canvas-wrap {
    border: 1px dashed #b8cdcb;
    border-radius: 14px;
    background:
        linear-gradient(0deg, rgba(245, 251, 250, .9), rgba(245, 251, 250, .9)),
        linear-gradient(90deg, rgba(11, 93, 91, .06) 1px, transparent 1px),
        linear-gradient(0deg, rgba(11, 93, 91, .06) 1px, transparent 1px);
    background-size: auto, 24px 24px, 24px 24px;
    overflow: auto;
}

#orgChartPage .org-canvas {
    position: relative;
    min-height: 520px;
    min-width: 100%;
}

#orgChartPage .org-lines {
    position: absolute;
    inset: 0;
    pointer-events: none;
}

#orgChartPage .org-node-card {
    position: absolute;
    width: 280px;
    border: 1px solid #cbe1df;
    border-radius: 14px;
    background: #ffffff;
    box-shadow: 0 10px 18px rgba(12, 43, 52, .08);
    padding: .75rem;
    cursor: move;
    z-index: 2;
}

#orgChartPage .org-node-card.node-selected {
    border-color: #0b5d5b;
    box-shadow: 0 0 0 .2rem rgba(11, 93, 91, .15);
}

#orgChartPage .org-node-head {
    display: flex;
    gap: .65rem;
}

#orgChartPage .org-avatar {
    width: 58px;
    height: 58px;
    border-radius: 10px;
    border: 1px solid #d8e4e2;
    background: #f4faf8;
    overflow: hidden;
    flex-shrink: 0;
}

#orgChartPage .org-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

#orgChartPage .org-avatar-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    color: #0b5d5b;
}

#orgChartPage .org-node-name {
    font-weight: 700;
    color: #1b2a36;
    line-height: 1.2;
}

#orgChartPage .org-node-position {
    font-size: .93rem;
    color: #0d5f5a;
}

#orgChartPage .org-node-unit {
    font-size: .82rem;
    color: #5a6f7f;
}

#orgChartPage .org-node-actions {
    margin-top: .55rem;
    padding-top: .5rem;
    border-top: 1px dashed #d5e3e1;
}

#orgChartPage .org-department-section {
    margin-top: 1rem;
}

#orgChartPage .org-easy-panel {
    border: 1px solid #d6e5e3;
    border-radius: 12px;
    background: #f8fcfb;
    padding: .8rem;
    margin-bottom: .85rem;
}

#orgChartPage .org-selected-hint {
    font-size: .92rem;
    color: #4f6473;
}

#orgChartPage .org-upload-box {
    background: #f7fbfb;
    border: 1px dashed #bdd9d6;
    border-radius: 12px;
    padding: .85rem;
}

#orgChartPage .modal-dialog {
    max-width: 1080px;
}

#orgChartPage .modal-dialog-scrollable .modal-content {
    max-height: calc(100vh - 2rem);
}

#orgChartPage .modal-dialog-scrollable .modal-body {
    overflow-y: auto;
}

#orgChartPage .modal-dialog-scrollable .modal-footer {
    position: sticky;
    bottom: 0;
    background: #fff;
    z-index: 2;
    border-top: 1px solid #dee2e6;
}


#mainChartModal .modal-dialog,
#nodeModal .modal-dialog {
    max-width: 1080px;
}

#mainChartModal .modal-dialog-scrollable .modal-content,
#nodeModal .modal-dialog-scrollable .modal-content {
    max-height: calc(100vh - 1rem);
}

#mainChartModal .modal-dialog-scrollable .modal-body,
#nodeModal .modal-dialog-scrollable .modal-body {
    overflow-y: auto;
    max-height: calc(100vh - 210px);
}

#mainChartModal .modal-footer,
#nodeModal .modal-footer {
    position: sticky;
    bottom: 0;
    background: #fff;
    z-index: 3;
    border-top: 1px solid #dee2e6;
}
@media (max-width: 991.98px) {
    #orgChartPage .org-node-card {
        width: 250px;
    }
}
</style>

<div id="orgChartPage"
     data-load-url="/nurse_srnh/admin/org_chart_load.php"
     data-save-url="/nurse_srnh/admin/org_chart_save.php"
     data-save-layout-url="/nurse_srnh/admin/org_chart_save_layout.php"
     data-staff-load-url="/nurse_srnh/admin/personnel_load.php">

    <div class="d-flex flex-wrap justify-content-between align-items-end mb-3 gap-2">
        <div>
            <h1 class="page-title h3 mb-1">ทำเนียบบุคลากร / Organization Chart</h1>
            <p class="text-soft mb-0">สร้างผังหลักและผังย่อยแผนกจากหน้าเดียว พร้อมลากวางและบันทึกตำแหน่ง</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <button type="button" class="btn btn-brand" id="btnCreateMainChart" data-bs-toggle="modal" data-bs-target="#mainChartModal">+ เพิ่มผังหลัก</button>
            <button type="button" class="btn btn-outline-primary" id="btnSaveLayout">บันทึก Layout</button>
            <button type="button" class="btn btn-outline-secondary" id="btnAutoArrange">Auto Arrange</button>
            <button type="button" class="btn btn-outline-danger" id="btnResetLayout">Reset Layout</button>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success glass-card border-0"><?= h($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger glass-card border-0"><?= h($error) ?></div>
    <?php endif; ?>

    <section class="org-toolbar mb-3">
        <div class="small text-soft">Flow: Empty state -> Create main chart -> Add department heads -> Create department sub-charts -> Add department members</div>
    </section>

    <section id="orgEmptyState" class="org-empty-state org-shell">
        <div class="org-empty-card">
            <div class="org-empty-icon">+</div>
            <div class="org-empty-title">ยังไม่มีผังองค์กร</div>
            <div class="org-empty-subtitle">เริ่มสร้างผังหลักก่อน แล้วค่อยแตกเป็นผังย่อยของแต่ละแผนก</div>
            <button type="button" class="btn btn-brand btn-lg" data-bs-toggle="modal" data-bs-target="#mainChartModal">+ เพิ่มผังหลัก</button>
        </div>
    </section>

    <section id="orgMainChartSection" class="d-none org-shell">
        <div class="org-easy-panel">
            <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between">
                <div class="org-selected-hint" id="easySelectedInfo">โหมดง่าย: คลิกเลือกการ์ดก่อน แล้วใช้ปุ่มด้านขวา</div>
                <div class="d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn-sm btn-outline-success" id="btnEasyAddChild" disabled>+ เพิ่มลูกน้อง</button>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="btnEasyCreateSubChart" disabled>+ สร้างผังย่อยแผนก</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="btnEasyEdit" disabled>แก้ไข</button>
                    <button type="button" class="btn btn-sm btn-outline-danger" id="btnEasyDelete" disabled>ลบ</button>
                </div>
            </div>
        </div>
        <div class="glass-card p-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="fw-semibold">ผังหลัก</div>
                <button type="button" class="btn btn-sm btn-outline-danger" id="btnDeleteMainChart">ลบผังหลัก</button>
            </div>
            <div id="orgMainCanvasWrap" class="org-canvas-wrap p-2">
                <div id="orgMainCanvas" class="org-canvas"></div>
            </div>
        </div>

        <div id="orgDepartmentSections" class="mt-3"></div>
    </section>
</div>

<div class="modal fade" id="mainChartModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <form id="mainChartForm" enctype="multipart/form-data">
                <div class="modal-header">
                    <h2 class="modal-title fs-5">เพิ่มผังหลัก</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="create_main_chart">
                    <input type="hidden" name="staff_profile_image" id="mainStaffProfileImage" value="">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">ชื่อผัง <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="chart_name" id="mainChartName" maxlength="191" required value="ผังโครงสร้างงานการพยาบาล">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">ชื่อหัวหน้าสูงสุด <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="full_name" id="mainFullName" maxlength="255" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">เลือกจากฐานข้อมูลเจ้าหน้าที่ (สำหรับหัวหน้าหลัก)</label>
                            <div class="row g-2">
                                <div class="col-md-5">
                                    <input type="text" class="form-control" id="mainStaffSearchInput" placeholder="ค้นหาเจ้าหน้าที่...">
                                </div>
                                <div class="col-md-7 d-flex gap-2">
                                    <select class="form-select" id="mainStaffSelect">
                                        <option value="">- เลือกเจ้าหน้าที่ -</option>
                                    </select>
                                    <button type="button" class="btn btn-outline-primary" id="btnApplyStaffToMain">ใช้ข้อมูล</button>
                                    <button type="button" class="btn btn-outline-info" id="btnRefreshStaffListMain">รีเฟรชรายชื่อ</button>
                                    <a href="/nurse_srnh/admin/staff.php" target="_blank" class="btn btn-outline-secondary" id="btnOpenStaffManagerMain">เปิดหน้า Staff</a>
                                </div>
                            </div>
                            <div class="form-text">เลือกเจ้าหน้าที่จากฐานข้อมูลมาเติมฟอร์มหัวหน้าผังหลักอัตโนมัติ</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">ตำแหน่ง</label>
                            <input type="text" class="form-control" name="position_name" id="mainPositionName" maxlength="255" placeholder="เช่น หัวหน้ากลุ่มงานการพยาบาล">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">แผนก</label>
                            <input type="text" class="form-control" name="department_name" id="mainDepartmentName" maxlength="191">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">หน่วยงาน</label>
                            <input type="text" class="form-control" name="unit_name" id="mainUnitName" maxlength="191">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">รูปบุคลากร (jpg, jpeg, png, gif, webp, max 5MB)</label>
                            <input type="file" class="form-control" name="profile_image" id="mainProfileImage" accept=".jpg,.jpeg,.png,.gif,.webp">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">โทรศัพท์</label>
                            <input type="text" class="form-control" name="phone" id="mainPhone" maxlength="50">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">เบอร์ภายใน</label>
                            <input type="text" class="form-control" name="internal_phone" id="mainInternalPhone" maxlength="50">
                        </div>
                        <div class="col-12">
                            <label class="form-label">หมายเหตุ</label>
                            <textarea class="form-control" name="note" id="mainNote" rows="3" maxlength="4000"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-brand">สร้างผังหลัก</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="nodeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <form id="nodeForm" enctype="multipart/form-data">
                <div class="modal-header">
                    <h2 class="modal-title fs-5" id="nodeModalTitle">เพิ่มบุคลากร</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" id="nodeFormAction" value="create_node">
                    <input type="hidden" name="node_id" id="nodeId" value="">
                    <input type="hidden" name="chart_id" id="nodeChartId" value="">
                    <input type="hidden" name="parent_node_id" id="nodeParentId" value="">
                    <input type="hidden" name="x_position" id="nodeX" value="">
                    <input type="hidden" name="y_position" id="nodeY" value="">
                    <input type="hidden" name="staff_profile_image" id="nodeStaffProfileImage" value="">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">ชื่อ-นามสกุล <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="full_name" id="nodeFullName" maxlength="255" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">เลือกจากฐานข้อมูลเจ้าหน้าที่ (Staff Manager)</label>
                            <div class="row g-2">
                                <div class="col-md-5">
                                    <input type="text" class="form-control" id="staffSearchInput" placeholder="ค้นหาเจ้าหน้าที่...">
                                </div>
                                <div class="col-md-7 d-flex gap-2">
                                    <select class="form-select" id="staffSelect">
                                        <option value="">- เลือกเจ้าหน้าที่ -</option>
                                    </select>
                                    <button type="button" class="btn btn-outline-primary" id="btnApplyStaffToNode">ใช้ข้อมูล</button>
                                    <button type="button" class="btn btn-outline-info" id="btnRefreshStaffList">รีเฟรชรายชื่อ</button>
                                    <a href="/nurse_srnh/admin/staff.php" target="_blank" class="btn btn-outline-secondary" id="btnOpenStaffManager">เปิดหน้า Staff</a>
                                </div>
                            </div>
                            <div class="form-text">ค้นหาแล้วเลือกเจ้าหน้าที่เพื่อเติมข้อมูลอัตโนมัติ หรือเปิดหน้า Staff เพื่อเพิ่มรายการใหม่</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">ประเภทบุคลากร</label>
                            <select class="form-select" name="personnel_type" id="nodePersonnelType">
                                <option value="executive">Executive</option>
                                <option value="department_head">Department Head</option>
                                <option value="staff" selected>Staff</option>
                                <option value="assistant">Assistant</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">ตำแหน่ง</label>
                            <input type="text" class="form-control" name="position_name" id="nodePositionName" maxlength="255">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">แผนก</label>
                            <input type="text" class="form-control" name="department_name" id="nodeDepartmentName" maxlength="191">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">หน่วยงาน</label>
                            <input type="text" class="form-control" name="unit_name" id="nodeUnitName" maxlength="191">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">สถานะ</label>
                            <select class="form-select" name="status" id="nodeStatus">
                                <option value="1" selected>Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">โทรศัพท์</label>
                            <input type="text" class="form-control" name="phone" id="nodePhone" maxlength="50">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">เบอร์ภายใน</label>
                            <input type="text" class="form-control" name="internal_phone" id="nodeInternalPhone" maxlength="50">
                        </div>
                        <div class="col-12">
                            <label class="form-label">หมายเหตุ</label>
                            <textarea class="form-control" name="note" id="nodeNote" rows="3" maxlength="4000"></textarea>
                        </div>
                        <div class="col-12">
                            <div class="org-upload-box">
                                <label class="form-label">รูปบุคลากร (jpg, jpeg, png, gif, webp, max 5MB)</label>
                                <input type="file" class="form-control" name="profile_image" id="nodeProfileImage" accept=".jpg,.jpeg,.png,.gif,.webp">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-brand" id="nodeSubmitBtn">บันทึก</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php $orgChartJsVer = @filemtime(__DIR__ . '/assets/js/org_chart.js'); ?>
<script src="https://cdn.jsdelivr.net/npm/interactjs/dist/interact.min.js"></script>
<script src="/nurse_srnh/admin/assets/js/org_chart.js?v=<?= (int)$orgChartJsVer ?>"></script>

<?php include __DIR__ . '/_footer.php'; ?>










