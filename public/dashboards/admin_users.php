<?php
require_once('/opt/mka/bootstrap.php');
$GLOBALS['current_dashboard'] = 'speechapp';
include('/opt/mka/dashboards/_init.php');
include('_menu_loader.php');
require_once('/opt/mka/core/Payment/StripeConfig.php');

require_once('/opt/mka/core/Billing/SLPBilling.php');
use MKA\Billing\SLPBilling;

// Check if user is admin or super_user
$userType = current_user_type();
$currentUserTier = $_SESSION['user_data']['plan_name'];
$pdo = $GLOBALS['pdo'];

if (!in_array($userType, ['super_user', 'enterprise_admin'])) {
    header("Location: /dashboards/speechapp.php");
    exit;
}

// For super_user: fetch all SLPs for the affiliation dropdown
$slpList = [];
if ($userType === 'super_user') {
    $slpStmt = $pdo->prepare("SELECT UserUUID, Name FROM mka_users WHERE user_type = 'enterprise_admin' ORDER BY Name ASC");
    $slpStmt->execute();
    $slpList = $slpStmt->fetchAll(PDO::FETCH_ASSOC);
}

// Super users can create enterprise_admin and end_user
// Enterprise admins can only create end_user
$canCreateAdmin = ($userType === 'super_user');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - SpeechApp</title>

    <link rel="shortcut icon"href="/dashboards/img/favicon.ico">
    <link href="css/bootstrap.min.css" rel="stylesheet" type="text/css">
    <link href="plugins/fontawesome/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="css/style.min.css?v=<?= ASSET_VER ?>" rel="stylesheet" type="text/css">
    <link href="plugins/toastr/css/toastr.min.css" rel="stylesheet">
    <script src="plugins/jquery/js/jquery.min.js"></script>

    <style>
        .user-type-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.85em;
            font-weight: 600;
        }
        .badge-super { background: #6f42c1; color: white; }
        .badge-admin { background: #0d6efd; color: white; }
        .badge-user { background: #6c757d; color: white; }

        .tier-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.85em;
        }
        .badge-lite { background: #ffc107; color: #000; }
        .badge-standard { background: #0dcaf0; color: #000; }
        .badge-pro { background: #198754; color: white; }
        .badge-enterprise { background: #6f42c1; color: white; }

        .modal-header {
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            padding: 1rem !important;
        }

        .modal-header .modal-title {
            margin: 0;
            flex: 1;
        }

        .modal-header .close {
            padding: 0;
            margin: 0;
            margin-left: 1rem;
            background: transparent;
            border: 0;
            font-size: 1.5rem;
            font-weight: 700;
            line-height: 1;
            opacity: .5;
        }

        .modal-header .close:hover {
            opacity: .75;
        }
    </style>
</head>
<body>
<div id="wrapper">
    <?=$menu?>

    <div id="page-wrapper" class="gray-bg dashbard-1">
        <?=$topbar?>

        <div class="row border-bottom white-bg dashboard-header">
            <div class="col-xl-8">
                <h1><i class="fa fa-users"></i> User Management</h1>
                <span class="text-muted">
                        <?php if ($userType === 'super_user'): ?>
                            Manage enterprise admins and end-users
                        <?php else: ?>
                            Manage your end-users (patients/clients)
                        <?php endif; ?>
                    </span>
            </div>

            <div class="wrapper wrapper-content">
                <?php if ($userType === 'enterprise_admin'): ?>
                    <?php
                    // Load billing info for enterprise admins


                    $slpUuid = $_SESSION['user_data']['user_uuid'];
                    $totalCapacity = SLPBilling::getTotalCapacity($slpUuid, $pdo);
                    $affiliatedCount = SLPBilling::getAffiliatedPatientCount($slpUuid, $pdo);
                    $availableSlots = SLPBilling::getAvailableSlots($slpUuid, $pdo);
                    $monthlyBill = SLPBilling::calculateMonthlyBill($slpUuid, $pdo);
                    $addonCosts = SLPBilling::getAddonCosts($slpUuid, $pdo);
                    ?>

                    <div class="row mb-4">
                        <div class="col-lg-12">
                            <div class="ibox">
                                <div class="ibox-title">
                                    <h5><i class="fa fa-chart-line"></i> Your Capacity & Billing</h5>
                                </div>
                                <div class="ibox-content">
                                    <div class="row mb-3">
                                        <div class="col-md-3 text-center">
                                            <div class="text-muted small mb-2">Total Capacity</div>
                                            <h2 class="mb-0 font-bold"><?= $totalCapacity ?></h2>
                                            <small class="text-muted">patient slots</small>
                                        </div>
                                        <div class="col-md-3 text-center">
                                            <div class="text-muted small mb-2">Slots Used</div>
                                            <h2 class="mb-0 font-bold text-navy"><?= $affiliatedCount ?></h2>
                                            <small class="text-muted">patients affiliated</small>
                                        </div>
                                        <div class="col-md-3 text-center">
                                            <div class="text-muted small mb-2">Available Slots</div>
                                            <h2 class="mb-0 font-bold <?= $availableSlots > 0 ? 'text-success' : 'text-warning' ?>">
                                                <?= $availableSlots ?>
                                            </h2>
                                            <small class="text-muted">slots remaining</small>
                                        </div>
                                        <div class="col-md-3 text-center">
                                            <div class="text-muted small mb-2">Current Bill</div>
                                            <h2 class="mb-0 font-bold text-success">$<?= number_format($monthlyBill, 2) ?></h2>
                                            <small class="text-muted">per month</small>
                                        </div>
                                    </div>

                                    <!-- Progress bar -->
                                    <div class="progress mb-3" style="height: 30px;">
                                        <div class="progress-bar <?= $availableSlots > 0 ? 'progress-bar-success' : 'progress-bar-warning' ?>"
                                             role="progressbar"
                                             style="width: <?= $totalCapacity > 0 ? ($affiliatedCount / $totalCapacity) * 100 : 0 ?>%">
                                            <strong><?= $affiliatedCount ?> / <?= $totalCapacity ?> slots used</strong>
                                        </div>
                                    </div>

                                    <?php if ($availableSlots <= 2 && $availableSlots > 0): ?>
                                        <div class="alert alert-warning">
                                            <i class="fa fa-exclamation-triangle"></i>
                                            <strong>Running low on capacity!</strong> You have only <?= $availableSlots ?> slot<?= $availableSlots != 1 ? 's' : '' ?> remaining. Purchase more slots below.
                                        </div>
                                    <?php elseif ($availableSlots === 0): ?>
                                        <div class="alert alert-danger">
                                            <i class="fa fa-ban"></i>
                                            <strong>No capacity available!</strong> You must purchase additional slots to add more patients.
                                        </div>
                                    <?php endif; ?>

                                    <!-- Billing breakdown -->
                                    <div class="row mb-3">
                                        <div class="col-md-12">
                                            <small class="text-muted">
                                                <strong>Billing Details:</strong>
                                                Base ($100)
                                                <?php if ($addonCosts > 0): ?>
                                                    + Add-ons ($<?= number_format($addonCosts, 2) ?>)
                                                <?php endif; ?>
                                                <?php if ($affiliatedCount > 0): ?>
                                                    - Credits ($<?= number_format($affiliatedCount * 10, 2) ?>)
                                                <?php endif; ?>
                                                = <strong>$<?= number_format($monthlyBill, 2) ?>/month</strong>
                                            </small>
                                        </div>
                                    </div>

                                    <!-- Purchase buttons -->
                                    <div class="text-center">
                                        <p class="text-muted mb-2"><strong>Need more capacity?</strong></p>
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-outline btn-primary" onclick="purchaseCapacityPack(1)">
                                                <i class="fa fa-plus"></i> 1 Slot<br>
                                                <small>$10/month</small>
                                            </button>
                                            <button type="button" class="btn btn-outline btn-primary" onclick="purchaseCapacityPack(5)">
                                                <i class="fa fa-plus"></i> 5 Slots<br>
                                                <small>$45/month</small>
                                            </button>
                                            <button type="button" class="btn btn-outline btn-primary" onclick="purchaseCapacityPack(10)">
                                                <i class="fa fa-plus"></i> 10 Slots<br>
                                                <small>$80/month</small>
                                            </button>
                                        </div>
                                        <div class="mt-2">
                                            <small class="text-muted">
                                                <i class="fa fa-info-circle"></i> Get $10/month credit back for each patient that subscribes
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-xl-4 text-right">
                        <?php if (in_array($userType, ['enterprise_admin', 'super_user'])): ?>
                            <button class="btn btn-primary mt-3" onclick="showInviteUserModal()">
                                <i class="fa fa-envelope"></i> Invite User
                            </button>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="wrapper wrapper-content">
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="ibox">
                                <div class="ibox-title">
                                    <h5>Users</h5>
                                    <div class="ibox-tools d-flex align-items-center gap-2">
                                        <button class="btn btn-sm btn-default" id="toggleInactiveBtn" onclick="toggleInactive()">
                                            <i class="fa fa-eye-slash"></i> Show Inactive
                                        </button>
                                        <input type="text" class="form-control form-control-sm" id="searchUsers"
                                               placeholder="Search name, email, provider..."
                                               style="width: 220px; display: inline-block;">
                                        <select class="form-control form-control-sm" id="filterUserType" style="width: 150px; display: inline-block;">
                                            <option value="">All Types</option>
                                            <?php if ($canCreateAdmin): ?>
                                                <option value="enterprise_admin">Enterprise Admins</option>
                                            <?php endif; ?>
                                            <option value="end_user">End Users</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="ibox-content">
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover" id="usersTable">
                                            <thead>
                                            <tr>
                                                <th>Name</th>
                                                <th>Email</th>
                                                <th>Type</th>
                                                <th>Provider</th>
                                                <th>Status</th>
                                                <th>Created</th>
                                                <th>Actions</th>
                                            </tr>
                                            </thead>
                                            <tbody id="usersTableBody">
                                            <tr>
                                                <td colspan="7" class="text-center">
                                                    <i class="fa fa-spinner fa-spin"></i> Loading users...
                                                </td>
                                            </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create User Modal -->
        <div class="modal fade" id="createUserModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Create New User</h5>
                        <button type="button" class="close" onclick="$('#createUserModal').modal('hide')">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div id="createUserError" class="alert alert-danger" style="display:none;"></div>

                        <form id="createUserForm">
                            <div class="form-group">
                                <label>Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="name" required>
                                <small class="form-text text-muted">Full name of the user</small>
                            </div>

                            <div class="form-group">
                                <label>Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" name="email" required>
                                <small class="form-text text-muted">Will be used for login</small>
                            </div>

                            <div class="form-group">
                                <label>Password <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" name="password" required minlength="8">
                                <small class="form-text text-muted">Minimum 8 characters</small>
                            </div>

                            <?php if ($canCreateAdmin): ?>
                                <div class="form-group">
                                    <label>User Type <span class="text-danger">*</span></label>
                                    <select class="form-control" name="user_type" id="userTypeSelect" required>
                                        <option value="enterprise_admin">Enterprise Admin</option>
                                        <option value="end_user" selected>End User (Patient/Client)</option>
                                    </select>
                                    <small class="form-text text-muted">
                                        Enterprise admins can create and manage their own end-users
                                    </small>
                                </div>

                                <!-- Tier selection ONLY for enterprise admins being created by super-user -->
                                <div class="form-group" id="tierGroup" style="display:none;">
                                    <label>Tier <span class="text-danger">*</span></label>
                                    <select class="form-control" name="tier" id="tierSelect">
                                        <option value="lite">Lite - 15 sounds/words</option>
                                        <option value="standard">Standard - 35 sounds/words</option>
                                        <option value="pro">Pro - 60 sounds/words</option>
                                        <option value="enterprise">Enterprise - Unlimited</option>
                                    </select>
                                </div>

                                <script>
                                    // Show tier selection ONLY when creating enterprise_admin
                                    $('#userTypeSelect').on('change', function() {
                                        if ($(this).val() === 'enterprise_admin') {
                                            $('#tierGroup').show();
                                            $('#tierSelect').prop('required', true);
                                        } else {
                                            $('#tierGroup').hide();
                                            $('#tierSelect').prop('required', false);
                                        }
                                    });
                                </script>
                            <?php else: ?>
                            <input type="hidden" name="user_type" value="end_user">
                                <!-- No tier selection - will inherit from parent admin -->
                                <div class="alert alert-info">
                                    <small><i class="fa fa-info-circle"></i> New users will automatically be assigned to your account's tier: <strong><?php echo htmlspecialchars($currentUserTier ?? 'Unknown'); ?></strong></small>
                                </div>
                            <?php endif; ?>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="$('#createUserModal').modal('hide')">Cancel</button>
                        <button type="button" class="btn btn-primary" onclick="createUser()" id="createUserBtn">
                            <i class="fa fa-save"></i> Create User
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit User Modal -->
        <div class="modal fade" id="editUserModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit User</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick="$('#editUserModal').modal('hide')">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div id="editUserError" class="alert alert-danger" style="display:none;"></div>

                        <form id="editUserForm">
                            <input type="hidden" name="user_uuid" id="editUserUuid">

                            <div class="form-group">
                                <label>Name</label>
                                <input type="text" class="form-control" name="name" id="editUserName">
                            </div>

                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" class="form-control" name="email" id="editUserEmail">
                            </div>

                            <div class="form-group">
                                <label>New Password</label>
                                <input type="password" class="form-control" name="password" id="editUserPassword" minlength="8">
                                <small class="form-text text-muted">Leave blank to keep current password</small>
                            </div>

                            <?php if ($userType === 'super_user'): ?>
                            <div class="form-group" id="affiliationGroup" style="display:none;">
                                <label>Affiliated SLP <span class="text-muted">(End Users only)</span></label>
                                <select class="form-control" name="slp_affiliation" id="editUserAffiliation">
                                    <option value="">— None (Super User) —</option>
                                    <?php foreach ($slpList as $slp): ?>
                                    <option value="<?= htmlspecialchars($slp['UserUUID']) ?>">
                                        <?= htmlspecialchars($slp['Name']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="form-text text-muted">Assigning an SLP gives them billing credit for this patient.</small>
                            </div>
                            <?php endif; ?>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="$('#editUserModal').modal('hide')">Cancel</button>
                        <button type="button" class="btn btn-primary" onclick="updateUser()">
                            <i class="fa fa-save"></i> Update User
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div class="modal fade" id="deleteUserModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title"><i class="fa fa-exclamation-triangle"></i> Confirm Deletion</h5>
                        <button type="button" class="close text-white" onclick="$('#deleteUserModal').modal('hide')">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to deactivate this user?</p>
                        <div class="alert alert-warning">
                            <strong>User:</strong> <span id="deleteUserName"></span><br>
                            <strong>Email:</strong> <span id="deleteUserEmail"></span>
                        </div>
                        <p><strong>Note:</strong> This will deactivate the user's account. They will not be able to log in.</p>
                        <input type="hidden" id="deleteUserUuid">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="$('#deleteUserModal').modal('hide')">Cancel</button>
                        <button type="button" class="btn btn-danger" onclick="confirmDeleteUser()">
                            <i class="fa fa-trash"></i> Deactivate User
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Invite User Modal -->
        <div class="modal fade" id="inviteUserModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <?php $inviteeLabel = ($userType === 'super_user') ? 'SLP/Patient' : 'Patient'; ?>
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fa fa-envelope"></i> Invite <?= $inviteeLabel ?></h5>
                        <button type="button" class="close" onclick="$('#inviteUserModal').modal('hide')">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div id="inviteUserError" class="alert alert-danger" style="display:none;"></div>
                        <div id="inviteUserSuccess" class="alert alert-success" style="display:none;"></div>

                        <div class="alert alert-info mb-3">
                            <i class="fa fa-info-circle"></i>
                            <strong>How it works:</strong> Send an invitation email to your <?= $inviteeLabel ?>.
                            They'll receive a link to create their account and will be automatically affiliated with you.
                        </div>

                        <form id="inviteUserForm">
                            <div class="form-group">
                                <label><?= $inviteeLabel ?> Name</label>
                                <input type="text" class="form-control" name="patient_name" placeholder="Enter <?= strtolower($inviteeLabel) ?>'s name (optional)">
                                <small class="form-text text-muted">Optional - helps personalize the invite email</small>
                            </div>

                            <div class="form-group">
                                <label><?= $inviteeLabel ?> Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" name="patient_email" placeholder="<?= strtolower($inviteeLabel) ?>@example.com" required>
                                <small class="form-text text-muted">Where the invitation will be sent</small>
                            </div>

                            <div class="alert alert-warning">
                                <small>
                                    <i class="fa fa-exclamation-triangle"></i>
                                    The invite link will expire in 7 days. <?= $inviteeLabel ?> must accept the invite to be affiliated with you.
                                </small>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="$('#inviteUserModal').modal('hide')">Cancel</button>
                        <button type="button" class="btn btn-primary" onclick="sendInvite()" id="sendInviteBtn">
                            <i class="fa fa-paper-plane"></i> Send Invite
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
        <script src="plugins/metismenu/js/metisMenu.min.js"></script>
        <script src="plugins/toastr/js/toastr.min.js"></script>
        <script src="js/inspinia.js?v=<?= ASSET_VER ?>"></script>
        <script src="https://js.stripe.com/v3/"></script>

        <script>
            // Configuration
            toastr.options = {
                "closeButton": true,
                "progressBar": true,
                "positionClass": "toast-top-right",
                "timeOut": "3000"
            };

            // Show/hide tier based on user type
            <?php if ($canCreateAdmin): ?>
            $('#userTypeSelect').on('change', function() {
                const userType = $(this).val();
                if (userType === 'enterprise_admin') {
                    $('#tierSelect').val('enterprise');
                } else {
                    $('#tierSelect').val('lite');
                }
            });
            <?php endif; ?>

            function showCreateUserModal() {
                $('#createUserForm')[0].reset();
                $('#createUserError').hide();
                $('#createUserModal').modal('show');
            }

            let allUsers = [];
            let showInactive = false;

            function loadUsers() {
                $.get('/dashboards/api/admin/list_users.php', function(data) {
                    if (data.status === 'success') {
                        allUsers = data.users;
                        applyFilters();
                    } else {
                        toastr.error('Failed to load users');
                        $('#usersTableBody').html('<tr><td colspan="7" class="text-center text-danger">Failed to load users</td></tr>');
                    }
                }).fail(function() {
                    toastr.error('Network error loading users');
                    $('#usersTableBody').html('<tr><td colspan="7" class="text-center text-danger">Network error</td></tr>');
                });
            }

            function toggleInactive() {
                showInactive = !showInactive;
                const btn = $('#toggleInactiveBtn');
                if (showInactive) {
                    btn.html('<i class="fa fa-eye-slash"></i> Hide Inactive').removeClass('btn-default').addClass('btn-warning');
                } else {
                    btn.html('<i class="fa fa-eye-slash"></i> Show Inactive').removeClass('btn-warning').addClass('btn-default');
                }
                applyFilters();
            }

            function isActiveUser(user) {
                const s = (user.Status || '').toLowerCase();
                return s === 'active' || s === 'trial';
            }

            function applyFilters() {
                const typeFilter = $('#filterUserType').val().toLowerCase();
                const search = $('#searchUsers').val().toLowerCase().trim();

                const filtered = allUsers.filter(user => {
                    if (!showInactive && !isActiveUser(user)) return false;
                    if (typeFilter && user.user_type !== typeFilter) return false;
                    if (!search) return true;
                    const haystack = [
                        user.Name || '',
                        user.Email || '',
                        user.user_type === 'enterprise_admin' ? 'slp' : 'end user',
                        user.provider_name || ''
                    ].join(' ').toLowerCase();
                    return haystack.includes(search);
                });

                renderUsers(filtered);
            }

            function renderUsers(users) {
                const tbody = $('#usersTableBody');
                tbody.empty();

                if (users.length === 0) {
                    tbody.html('<tr><td colspan="7" class="text-center text-muted">No users found</td></tr>');
                    return;
                }

                users.forEach(user => {
                    const statusBadge = getStatusBadge(user);
                    const createdDate = new Date(user.CreatedAt).toLocaleDateString();
                    const typLabel = user.user_type === 'enterprise_admin' ? 'SLP' : 'End User';
                    const providerCell = user.user_type === 'end_user'
                        ? escapeHtml(user.provider_name || '—')
                        : '—';

                    const row = `
                <tr>
                    <td><strong>${escapeHtml(user.Name)}</strong></td>
                    <td>${escapeHtml(user.Email)}</td>
                    <td>${typLabel}</td>
                    <td>${providerCell}</td>
                    <td>${statusBadge}</td>
                    <td>${createdDate}</td>
                    <td>
                        <button class="btn btn-sm btn-primary" onclick="editUser('${user.UserUUID}')" title="Edit">
                            <i class="fa fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="deleteUser('${user.UserUUID}', '${escapeHtml(user.Name)}', '${escapeHtml(user.Email)}')" title="Delete">
                            <i class="fa fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
                    tbody.append(row);
                });

                // Mobile: hide non-essential columns, add Details expand button
                if (window.MKAMobile && MKAMobile.isMobile()) {
                    MKAMobile.initTable(document.getElementById('usersTable'), {
                        titleCol : 0,        // Name column stays visible
                        hideCols : [1, 3, 5] // Hide Email, Provider, Created on mobile
                    });
                }
            }

            function getUserTypeBadge(userType) {
                const badges = {
                    'super_user': '<span class="user-type-badge badge-super">Super User</span>',
                    'enterprise_admin': '<span class="user-type-badge badge-admin">Enterprise Admin</span>',
                    'end_user': '<span class="user-type-badge badge-user">End User</span>'
                };
                return badges[userType] || '<span class="user-type-badge badge-user">Unknown</span>';
            }

            function getTierBadge(tierName) {
                if (!tierName) return '<span class="badge badge-secondary">N/A</span>';

                const badges = {
                    'lite': '<span class="tier-badge badge-lite">Lite</span>',
                    'standard': '<span class="tier-badge badge-standard">Standard</span>',
                    'pro': '<span class="tier-badge badge-pro">Pro</span>',
                    'enterprise': '<span class="tier-badge badge-enterprise">Enterprise</span>'
                };
                return badges[tierName] || `<span class="badge badge-secondary">${tierName}</span>`;
            }

            function getStatusBadge(user) {
                const sub = (user.subscription_status || '').toLowerCase();
                if (sub === 'active') {
                    return '<span class="badge badge-success"><i class="fa fa-check-circle"></i> Active</span>';
                } else if (sub === 'trial') {
                    return '<span class="badge badge-warning" style="background:#f0ad4e; color:#000;"><i class="fa fa-clock"></i> Trial</span>';
                } else {
                    return '<span class="badge badge-danger"><i class="fa fa-times-circle"></i> Inactive</span>';
                }
            }

            function createUser() {
                const formData = {};
                $('#createUserForm').serializeArray().forEach(field => {
                    formData[field.name] = field.value;
                });

                // Validation
                if (!formData.name || !formData.email || !formData.password) {
                    $('#createUserError').text('Please fill in all required fields').show();
                    return;
                }

                if (formData.password.length < 8) {
                    $('#createUserError').text('Password must be at least 8 characters').show();
                    return;
                }

                // Disable button
                $('#createUserBtn').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Creating...');
                $('#createUserError').hide();

                $.ajax({
                    url: '/dashboards/api/admin/create_user.php',
                    method: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify(formData),
                    success: function(data) {
                        if (data.status === 'success') {
                            $('#createUserModal').modal('hide');
                            $('#createUserForm')[0].reset();
                            loadUsers();
                            toastr.success('User created successfully!');
                        } else {
                            $('#createUserError').text(data.message || 'Failed to create user').show();
                        }
                    },
                    error: function(xhr) {
                        const msg = xhr.responseJSON?.message || 'Network error occurred';
                        $('#createUserError').text(msg).show();
                    },
                    complete: function() {
                        $('#createUserBtn').prop('disabled', false).html('<i class="fa fa-save"></i> Create User');
                    }
                });
            }

            function editUser(userUuid) {
                $.get('/dashboards/api/admin/get_user.php?user_uuid=' + userUuid, function(data) {
                    if (data.status === 'success') {
                        const user = data.user;
                        $('#editUserUuid').val(user.UserUUID);
                        $('#editUserName').val(user.Name);
                        $('#editUserEmail').val(user.Email);
                        $('#editUserPassword').val('');
                        $('#editUserError').hide();

                        // Show affiliation field only for super_user editing an end_user
                        const affGroup = $('#affiliationGroup');
                        if (affGroup.length && user.user_type === 'end_user') {
                            $('#editUserAffiliation').val(user.current_slp_uuid || '');
                            affGroup.show();
                        } else if (affGroup.length) {
                            affGroup.hide();
                        }

                        $('#editUserModal').modal('show');
                    } else {
                        toastr.error('Failed to load user data');
                    }
                }).fail(function() {
                    toastr.error('Network error loading user');
                });
            }

            function updateUser() {
                const formData = {};
                $('#editUserForm').serializeArray().forEach(field => {
                    if (field.value) { // Only include non-empty fields
                        formData[field.name] = field.value;
                    }
                });

                // Always send slp_affiliation if the field is visible (empty = remove affiliation)
                const affGroup = $('#affiliationGroup');
                if (affGroup.length && affGroup.is(':visible')) {
                    formData.slp_affiliation = $('#editUserAffiliation').val();
                }

                $.ajax({
                    url: '/dashboards/api/admin/update_user.php',
                    method: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify(formData),
                    success: function(data) {
                        if (data.status === 'success') {
                            $('#editUserModal').modal('hide');
                            loadUsers();
                            toastr.success('User updated successfully!');
                        } else {
                            $('#editUserError').text(data.message || 'Failed to update user').show();
                        }
                    },
                    error: function(xhr) {
                        const msg = xhr.responseJSON?.message || 'Network error occurred';
                        $('#editUserError').text(msg).show();
                    }
                });
            }

            function deleteUser(userUuid, userName, userEmail) {
                $('#deleteUserUuid').val(userUuid);
                $('#deleteUserName').text(userName);
                $('#deleteUserEmail').text(userEmail);
                $('#deleteUserModal').modal('show');
            }

            function confirmDeleteUser() {
                const userUuid = $('#deleteUserUuid').val();

                $.ajax({
                    url: '/dashboards/api/admin/delete_user.php',
                    method: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({ user_uuid: userUuid }),
                    success: function(data) {
                        if (data.status === 'success') {
                            $('#deleteUserModal').modal('hide');
                            loadUsers();
                            toastr.success('User deactivated successfully!');
                        } else {
                            toastr.error(data.message || 'Failed to delete user');
                        }
                    },
                    error: function(xhr) {
                        const msg = xhr.responseJSON?.message || 'Network error occurred';
                        toastr.error(msg);
                    }
                });
            }

            function escapeHtml(text) {
                const map = {
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#039;'
                };
                return text.replace(/[&<>"']/g, m => map[m]);
            }

            // Load users on page load
            $(document).ready(function() {
                loadUsers();

                $('#filterUserType').on('change', applyFilters);
                $('#searchUsers').on('input', applyFilters);
            });



            <?php if ($userType === 'enterprise_admin'): ?>
            const stripe = Stripe('<?= \MKA\Payment\StripeConfig::getPublishableKey() ?>');

            function purchaseCapacityPack(packSize) {
                // Show loading state
                toastr.info('Redirecting to payment...');

                fetch('/dashboards/api/billing/purchase_capacity_pack.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        slp_uuid: '<?= $slpUuid ?>',
                        pack_size: packSize
                    })
                })
                    .then(r => r.json())
                    .then(data => {
                        if (data.status === 'success' && data.session_id) {
                            stripe.redirectToCheckout({ sessionId: data.session_id });
                        } else {
                            toastr.error(data.message || 'Failed to create checkout');
                        }
                    })
                    .catch(err => {
                        toastr.error('Error purchasing capacity pack');
                        console.error(err);
                    });
            }
            <?php endif; ?>

            //Invite users
            function showInviteUserModal() {
                $('#inviteUserForm')[0].reset();
                $('#inviteUserError').hide();
                $('#inviteUserSuccess').hide();
                $('#inviteUserModal').modal('show');
            }

            function sendInvite() {
                const formData = {};
                $('#inviteUserForm').serializeArray().forEach(field => {
                    formData[field.name] = field.value;
                });

                // Add SLP UUID
                formData.slp_uuid = '<?= $_SESSION['user_data']['user_uuid'] ?>';

                // Validation
                if (!formData.patient_email) {
                    $('#inviteUserError').text('Please enter a patient email address').show();
                    return;
                }

                // Validate email format
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(formData.patient_email)) {
                    $('#inviteUserError').text('Please enter a valid email address').show();
                    return;
                }

                // Disable button
                $('#sendInviteBtn').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Sending...');
                $('#inviteUserError').hide();
                $('#inviteUserSuccess').hide();

                $.ajax({
                    url: '/dashboards/api/admin/send_invite.php',
                    method: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify(formData),
                    success: function(data) {
                        if (data.status === 'success') {
                            $('#inviteUserSuccess').text(data.message).show();
                            $('#inviteUserForm')[0].reset();

                            // Show success message and close modal after 2 seconds
                            setTimeout(function() {
                                $('#inviteUserModal').modal('hide');
                                toastr.success('Invitation sent successfully!');
                                loadUsers(); // Refresh user list
                            }, 2000);
                        } else {
                            $('#inviteUserError').text(data.message || 'Failed to send invite').show();
                        }
                    },
                    error: function(xhr) {
                        const msg = xhr.responseJSON?.message || 'Network error occurred';
                        $('#inviteUserError').text(msg).show();
                    },
                    complete: function() {
                        $('#sendInviteBtn').prop('disabled', false).html('<i class="fa fa-paper-plane"></i> Send Invite');
                    }
                });
            }
        </script>



</body>
</html>