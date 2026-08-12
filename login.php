<?php
require __DIR__.'/bootstrap.php';
if (!empty($_SESSION['user_id'])) { header('Location: dashboard.php'); exit; }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf(); $email=trim($_POST['email'] ?? ''); $password=$_POST['password'] ?? '';
    $stmt=$conn->prepare("SELECT id,full_name,password,role,status FROM users WHERE email=? LIMIT 1"); $stmt->bind_param('s',$email); $stmt->execute(); $user=$stmt->get_result()->fetch_assoc();
    if (!$user || $user['status'] !== 'active' || !password_verify($password,$user['password'])) $error='Invalid email, password, or account status.';
    else { session_regenerate_id(true); $_SESSION['user_id']=(int)$user['id']; $_SESSION['role']=$user['role']; $_SESSION['name']=$user['full_name']; audit($conn,'login','user',(int)$user['id']); header('Location: dashboard.php'); exit; }
}
layout_start('Sign in'); ?>
<section class="panel" style="max-width:460px;margin:70px auto"><h1>Sign in</h1><p class="muted">Use your approved Asset Management account.</p><?php if($error): ?><p class="notice error"><?=e($error)?></p><?php endif; ?><form method="post"><input type="hidden" name="csrf" value="<?=csrf()?>"><label>Email<input type="email" name="email" autocomplete="email" required></label><label>Password<input type="password" name="password" autocomplete="current-password" required></label><button>Sign in</button></form><p class="muted">First installation? <a href="create_super_admin.php">Create the super administrator</a>.</p></section>
<?php layout_end();
