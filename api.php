<?php
// Academic Portfolio API - PHP 7.4+ with SQLite (PDO)
session_set_cookie_params(['httponly'=>true,'samesite'=>'Strict']);
session_start();
header('Content-Type: application/json');
$db = new PDO('sqlite:' . __DIR__ . '/database/portfolio.sqlite');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->exec("CREATE TABLE IF NOT EXISTS works(id INTEGER PRIMARY KEY AUTOINCREMENT,category TEXT,title TEXT,description TEXT,filename TEXT,original TEXT,is_image INTEGER,created_at TEXT DEFAULT CURRENT_TIMESTAMP)");
const CATS = ['quiz','long_quiz','midterms','finals','activity','project'];
const ALLOWED = ['jpg','jpeg','png','gif','webp','pdf','doc','docx','ppt','pptx','xls','xlsx','txt','zip','rar'];
const IMAGES = ['jpg','jpeg','png','gif','webp'];
function out($d,$c=200){ http_response_code($c); echo json_encode($d); exit; }
function admin(){ if(empty($_SESSION['admin'])) out(['error'=>'Admin login required'],403); }
$in = json_decode(file_get_contents('php://input'), true) ?: [];
$a = $_GET['action'] ?? '';

if ($a === 'list') {
  $cat = $_GET['category'] ?? '';
  $s = $cat ? $db->prepare("SELECT * FROM works WHERE category=? ORDER BY id DESC") : $db->prepare("SELECT * FROM works ORDER BY id DESC");
  $cat ? $s->execute([$cat]) : $s->execute();
  out(['admin'=>!empty($_SESSION['admin']),'works'=>$s->fetchAll(PDO::FETCH_ASSOC)]);
}
if ($a === 'login') {
  sleep(1); // slows brute force
  $r = $db->query("SELECT * FROM admin LIMIT 1")->fetch(PDO::FETCH_ASSOC);
  $h = hash_pbkdf2('sha256', $in['password'] ?? '', $r['salt'], 100000, 64);
  if (strtolower(trim($in['email'] ?? '')) === strtolower($r['email']) && hash_equals($r['hash'], $h)) {
    session_regenerate_id(true); $_SESSION['admin'] = true; out(['ok'=>true]);
  }
  out(['error'=>'Wrong email or password'],401);
}
if ($a === 'logout') { session_destroy(); out(['ok'=>true]); }
if ($a === 'upload') {
  admin();
  $cat = $_POST['category'] ?? '';
  if (!in_array($cat, CATS)) out(['error'=>'Bad category'],400);
  $title = trim($_POST['title'] ?? '') ?: 'Untitled';
  $desc = trim($_POST['description'] ?? '');
  $name = ''; $orig = ''; $isImg = 0;
  if (!empty($_FILES['file']['name'])) {
    if ($_FILES['file']['error'] !== 0) out(['error'=>'Upload failed (file too big? check php.ini upload_max_filesize)'],400);
    $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ALLOWED)) out(['error'=>'File type not allowed'],400);
    $name = bin2hex(random_bytes(8)) . '.' . $ext;
    move_uploaded_file($_FILES['file']['tmp_name'], __DIR__ . '/uploads/' . $name);
    $orig = basename($_FILES['file']['name']); $isImg = in_array($ext, IMAGES) ? 1 : 0;
  }
  $db->prepare("INSERT INTO works(category,title,description,filename,original,is_image) VALUES(?,?,?,?,?,?)")->execute([$cat,$title,$desc,$name,$orig,$isImg]);
  out(['ok'=>true]);
}
if ($a === 'update') {
  admin();
  $db->prepare("UPDATE works SET title=?,description=? WHERE id=?")->execute([$in['title'] ?? '', $in['description'] ?? '', (int)($in['id'] ?? 0)]);
  out(['ok'=>true]);
}
if ($a === 'delete') {
  admin(); $id = (int)($in['id'] ?? 0);
  $s = $db->prepare("SELECT filename FROM works WHERE id=?"); $s->execute([$id]);
  $f = $s->fetchColumn();
  if ($f && is_file(__DIR__.'/uploads/'.$f)) unlink(__DIR__.'/uploads/'.$f);
  $db->prepare("DELETE FROM works WHERE id=?")->execute([$id]);
  out(['ok'=>true]);
}
if ($a === 'password') {
  admin();
  $new = $in['password'] ?? ''; $email = trim($in['email'] ?? '');
  if (strlen($new) < 8 || !filter_var($email, FILTER_VALIDATE_EMAIL)) out(['error'=>'Valid email and 8+ character password required'],400);
  $salt = bin2hex(random_bytes(16));
  $db->prepare("UPDATE admin SET email=?,salt=?,hash=?")->execute([$email,$salt,hash_pbkdf2('sha256',$new,$salt,100000,64)]);
  out(['ok'=>true]);
}
out(['error'=>'Unknown action'],400);
