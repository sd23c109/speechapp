
<?php
// Get user type
$stmt = $GLOBALS['pdo']->prepare("SELECT user_type FROM mka_users WHERE UserUUID = ?");
$stmt->execute([current_user_uuid()]);
$userType = $stmt->fetchColumn();
?>

<li><a href="speechapp.php"><i class="fa fa-pencil-square"></i> Exercises</a></li>

<?php if (in_array($userType, ['super_user', 'enterprise_admin'])): ?>
    <li class="nav-header">Administration</li>
    <li><a href="admin_users.php"><i class="fa fa-users"></i> Manage Users</a></li>
    <li><a href="admin_content.php"><i class="fa fa-book"></i> Manage Content</a></li>
<?php endif; ?>

<?php if ($userType === 'super_user'): ?>
    <li class="nav-header">Legal</li>
    <li><a href="terms_of_use.php"><i class="fa fa-file-text-o"></i> Terms of Use</a></li>
<?php endif; ?>
