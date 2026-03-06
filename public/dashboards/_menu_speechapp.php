
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
    <?php if ($userType === 'super_user'): ?>
        <li><a href="admin_pricing.php"><i class="fa fa-dollar-sign"></i> Set Pricing</a></li>
    <?php endif; ?>
<?php endif; ?>



