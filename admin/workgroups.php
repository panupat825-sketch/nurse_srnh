<?php
require_once __DIR__ . '/../bootstrap.php';
require_admin_login();

function in_str($k,$m=null){$v=isset($_POST[$k])?trim((string)$_POST[$k]):''; if($m!==null&&function_exists('mb_substr')){$v=mb_substr($v,0,(int)$m,'UTF-8');} return $v;}
function in_int($k,$d=0){if(!isset($_POST[$k])||$_POST[$k]==='')return (int)$d; return (int)$_POST[$k];}
function ensure_workgroups_table($db){
$db->exec("CREATE TABLE IF NOT EXISTS workgroups (
id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
group_code VARCHAR(50) NULL,
group_name VARCHAR(191) NOT NULL,
sort_order INT NOT NULL DEFAULT 0,
is_active TINYINT(1) NOT NULL DEFAULT 1,
note TEXT NULL,
created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
UNIQUE KEY uq_group_code (group_code),
UNIQUE KEY uq_group_name (group_name),
INDEX idx_sort_order (sort_order),
INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

$error=null; $editItem=null;
try{ensure_workgroups_table($db);}catch(Exception $e){$error='ไม่สามารถเตรียมตารางกลุ่มงานได้';}

if($_SERVER['REQUEST_METHOD']==='POST'&&$error===null){
$action=isset($_POST['action'])?trim((string)$_POST['action']):'';
if($action==='save'){
$id=in_int('id',0); $code=in_str('group_code',50); $name=in_str('group_name',191); $sort=in_int('sort_order',0); $active=in_int('is_active',1)===1?1:0; $note=in_str('note',4000);
if($name===''){$error='กรุณากรอกชื่อกลุ่มงาน';}else{
try{
if($id>0){$st=$db->prepare("UPDATE workgroups SET group_code=:group_code, group_name=:group_name, sort_order=:sort_order, is_active=:is_active, note=:note, updated_at=NOW() WHERE id=:id LIMIT 1"); $st->execute(array('group_code'=>$code!==''?$code:null,'group_name'=>$name,'sort_order'=>$sort,'is_active'=>$active,'note'=>$note!==''?$note:null,'id'=>$id)); flash('success','แก้ไขกลุ่มงานเรียบร้อยแล้ว');}
else{$st=$db->prepare("INSERT INTO workgroups (group_code,group_name,sort_order,is_active,note,created_at,updated_at) VALUES (:group_code,:group_name,:sort_order,:is_active,:note,NOW(),NOW())"); $st->execute(array('group_code'=>$code!==''?$code:null,'group_name'=>$name,'sort_order'=>$sort,'is_active'=>$active,'note'=>$note!==''?$note:null)); flash('success','เพิ่มกลุ่มงานเรียบร้อยแล้ว');}
redirect('/nurse_srnh/admin/workgroups.php');
}catch(PDOException $e){$error=((int)$e->getCode()===23000)?'รหัสหรือชื่อกลุ่มงานซ้ำ':'บันทึกข้อมูลไม่สำเร็จ';}
}
}
if($action==='delete'){$id=in_int('id',0); if($id>0){$chk=$db->prepare('SELECT COUNT(*) FROM subdepartments WHERE workgroup_id=:id'); $chk->execute(array('id'=>$id)); if((int)$chk->fetchColumn()>0){flash('error','ลบไม่ได้: ยังมีแผนกย่อยในกลุ่มงานนี้');} else {$st=$db->prepare('DELETE FROM workgroups WHERE id=:id LIMIT 1'); $st->execute(array('id'=>$id)); flash('success','ลบกลุ่มงานเรียบร้อยแล้ว');}} redirect('/nurse_srnh/admin/workgroups.php');}
}

if(isset($_GET['edit'])&&(int)$_GET['edit']>0){$st=$db->prepare('SELECT * FROM workgroups WHERE id=:id LIMIT 1');$st->execute(array('id'=>(int)$_GET['edit']));$editItem=$st->fetch(PDO::FETCH_ASSOC);}

$q=isset($_GET['q'])?trim((string)$_GET['q']):''; $status=isset($_GET['status'])?trim((string)$_GET['status']):'all';
$sql='SELECT wg.*, (SELECT COUNT(*) FROM subdepartments sd WHERE sd.workgroup_id=wg.id) AS sub_count FROM workgroups wg WHERE 1=1'; $params=array();
if($q!==''){ $sql.=' AND (wg.group_name LIKE :q OR wg.group_code LIKE :q OR wg.note LIKE :q)'; $params['q']='%'.$q.'%'; }
if($status==='active')$sql.=' AND wg.is_active=1'; elseif($status==='inactive')$sql.=' AND wg.is_active=0';
$sql.=' ORDER BY wg.sort_order ASC, wg.id DESC';
$st=$db->prepare($sql); $st->execute($params); $items=$st->fetchAll(PDO::FETCH_ASSOC);

$title='จัดการกลุ่มงาน'; $success=flash('success'); $flashError=flash('error'); include __DIR__.'/_header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-end mb-3 gap-2"><div><h1 class="page-title h3 mb-1">จัดการกลุ่มงาน</h1><p class="text-soft mb-0">กำหนดกลุ่มงานหลักก่อนสร้างแผนกย่อย</p></div></div>
<?php if($success): ?><div class="alert alert-success glass-card border-0"><?= h($success) ?></div><?php endif; ?>
<?php if($flashError): ?><div class="alert alert-danger glass-card border-0"><?= h($flashError) ?></div><?php endif; ?>
<?php if($error): ?><div class="alert alert-danger glass-card border-0"><?= h($error) ?></div><?php endif; ?>
<div class="row g-4">
<div class="col-xl-5"><div class="glass-card p-4"><h2 class="h5 mb-3"><?= $editItem?'แก้ไขกลุ่มงาน #'.(int)$editItem['id']:'เพิ่มกลุ่มงานใหม่' ?></h2><form method="post"><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= h(isset($editItem['id'])?$editItem['id']:'') ?>"><div class="row g-3"><div class="col-md-5"><label class="form-label">รหัส</label><input class="form-control" name="group_code" maxlength="50" value="<?= h(isset($editItem['group_code'])?$editItem['group_code']:'') ?>"></div><div class="col-md-7"><label class="form-label">ชื่อกลุ่มงาน <span class="text-danger">*</span></label><input class="form-control" name="group_name" maxlength="191" required value="<?= h(isset($editItem['group_name'])?$editItem['group_name']:'') ?>"></div><div class="col-md-6"><label class="form-label">Sort</label><input class="form-control" type="number" name="sort_order" value="<?= h((string)(isset($editItem['sort_order'])?$editItem['sort_order']:0)) ?>"></div><div class="col-md-6"><label class="form-label">สถานะ</label><select class="form-select" name="is_active"><option value="1" <?= (!isset($editItem['is_active'])||(int)$editItem['is_active']===1)?'selected':'' ?>>Active</option><option value="0" <?= (isset($editItem['is_active'])&&(int)$editItem['is_active']===0)?'selected':'' ?>>Inactive</option></select></div><div class="col-12"><label class="form-label">หมายเหตุ</label><textarea class="form-control" name="note" rows="3"><?= h(isset($editItem['note'])?$editItem['note']:'') ?></textarea></div></div><div class="d-flex gap-2 mt-3"><button class="btn btn-brand" type="submit">บันทึก</button><a class="btn btn-outline-secondary" href="/nurse_srnh/admin/workgroups.php">ล้างฟอร์ม</a></div></form></div></div>
<div class="col-xl-7"><div class="glass-card p-4"><div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3"><h2 class="h5 mb-0">รายการกลุ่มงาน</h2><form method="get" class="d-flex gap-2"><input class="form-control form-control-sm" name="q" placeholder="ค้นหา" value="<?= h($q) ?>"><select class="form-select form-select-sm" name="status"><option value="all" <?= $status==='all'?'selected':'' ?>>ทั้งหมด</option><option value="active" <?= $status==='active'?'selected':'' ?>>Active</option><option value="inactive" <?= $status==='inactive'?'selected':'' ?>>Inactive</option></select><button class="btn btn-sm btn-outline-primary" type="submit">กรอง</button></form></div><div class="table-responsive"><table class="table align-middle"><thead><tr><th style="width:70px;">ID</th><th>กลุ่มงาน</th><th style="width:80px;">Sort</th><th style="width:110px;">แผนกย่อย</th><th style="width:110px;">สถานะ</th><th style="width:140px;"></th></tr></thead><tbody><?php foreach($items as $it): ?><tr><td>#<?= (int)$it['id'] ?></td><td><div class="fw-semibold"><?= h($it['group_name']) ?></div><div class="small text-soft"><?= h((string)$it['group_code']) ?></div></td><td><?= (int)$it['sort_order'] ?></td><td><?= (int)$it['sub_count'] ?></td><td><?= (int)$it['is_active']===1?'<span class="badge text-bg-success">Active</span>':'<span class="badge text-bg-secondary">Inactive</span>' ?></td><td class="text-end"><a class="btn btn-sm btn-outline-primary" href="/nurse_srnh/admin/workgroups.php?edit=<?= (int)$it['id'] ?>">แก้ไข</a><form method="post" class="d-inline" onsubmit="return confirm('ยืนยันการลบ?');"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$it['id'] ?>"><button class="btn btn-sm btn-outline-danger" type="submit">ลบ</button></form></td></tr><?php endforeach; ?><?php if(count($items)===0): ?><tr><td colspan="6" class="text-center text-muted py-4">ยังไม่มีข้อมูลกลุ่มงาน</td></tr><?php endif; ?></tbody></table></div></div></div>
</div>
<?php include __DIR__ . '/_footer.php'; ?>
