<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdmin();

$pageTitle = 'Manage Users';
$errors = [];
$success = '';

// Check edit mode
$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$editUser = null;

if ($editId > 0) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $editId);
    $stmt->execute();
    $editUser = $stmt->get_result()->fetch_assoc();
}

// Handle Add/Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_user') {
        $fullName = sanitize($_POST['full_name'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (empty($fullName) || empty($email) || empty($password)) {
            $errors[] = "All fields are required.";
        } elseif (!isValidEmail($email)) {
            $errors[] = "Invalid email format.";
        } elseif ($password !== $confirmPassword) {
            $errors[] = "Passwords do not match.";
        } else {
            // Check unique email
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $errors[] = "Email already in use.";
            } else {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO users (full_name, email, password, role, is_verified, created_at, updated_at) VALUES (?, ?, ?, 'admin', 1, NOW(), NOW())");
                $stmt->bind_param("sss", $fullName, $email, $hashedPassword);
                if ($stmt->execute()) {
                    logAudit($conn, getCurrentUserId(), "Added new admin user: $email");
                    $_SESSION['success_msg'] = "Admin user added successfully.";
                    redirect('admin/users.php');
                } else {
                    $errors[] = "Failed to add user.";
                }
            }
        }
    } elseif ($action === 'update_user' && $editId > 0) {
        $fullName = sanitize($_POST['full_name'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $role = sanitize($_POST['role'] ?? 'admin');
        $password = $_POST['password'] ?? '';

        if (empty($fullName) || empty($email)) {
            $errors[] = "Name and email are required.";
        } elseif (!isValidEmail($email)) {
            $errors[] = "Invalid email format.";
        } else {
            // Check unique email excluding current user
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->bind_param("si", $email, $editId);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $errors[] = "Email already in use by another account.";
            } else {
                if (!empty($password)) {
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, role = ?, password = ?, updated_at = NOW() WHERE id = ?");
                    $stmt->bind_param("ssssi", $fullName, $email, $role, $hashedPassword, $editId);
                } else {
                    $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, role = ?, updated_at = NOW() WHERE id = ?");
                    $stmt->bind_param("sssi", $fullName, $email, $role, $editId);
                }
                
                if ($stmt->execute()) {
                    logAudit($conn, getCurrentUserId(), "Modified user: $email");
                    $_SESSION['success_msg'] = "User updated successfully.";
                    redirect('admin/users.php');
                } else {
                    $errors[] = "Failed to update user.";
                }
            }
        }
    }
}

// Display messages
if (isset($_SESSION['success_msg'])) {
    $success = $_SESSION['success_msg'];
    unset($_SESSION['success_msg']);
}

// Fetch all admin users
$stmt = $conn->prepare("SELECT id, full_name, email, created_at, role FROM users WHERE role = 'admin' ORDER BY created_at DESC");
$stmt->execute();
$adminUsers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container mt-4 fade-in">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="section-title text-gold m-0">Manage Admin Users</h2>
        <?php if (!$editUser): ?>
        <button class="btn btn-gold" type="button" data-bs-toggle="collapse" data-bs-target="#addUserForm" aria-expanded="false" aria-controls="addUserForm">
            <i class="bi bi-plus-circle me-1"></i> Add Admin
        </button>
        <?php endif; ?>
    </div>

    <?= displayMessages($errors, $success) ?>

    <?php if ($editUser): ?>
    <div class="card bg-dark text-light border-secondary mb-4 slide-up">
        <div class="card-header border-secondary text-gold">Edit User: <?= htmlspecialchars($editUser['full_name']) ?></div>
        <div class="card-body">
            <form method="POST" action="">
                <input type="hidden" name="action" value="update_user">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($editUser['full_name']) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($editUser['email']) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Role</label>
                        <select name="role" class="form-control">
                            <option value="admin" <?= $editUser['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                            <option value="buyer" <?= $editUser['role'] === 'buyer' ? 'selected' : '' ?>>Buyer</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">New Password (leave empty to keep current)</label>
                        <input type="password" name="password" class="form-control">
                    </div>
                </div>
                <div class="mt-4">
                    <button type="submit" class="btn btn-gold">Update User</button>
                    <a href="users.php" class="btn btn-secondary ms-2">Cancel</a>
                </div>
            </form>
        </div>
    </div>
    <?php else: ?>
    <div class="collapse mb-4 slide-up" id="addUserForm">
        <div class="card bg-dark text-light border-secondary">
            <div class="card-header border-secondary text-gold">Add New Admin User</div>
            <div class="card-body">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="add_user">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="full_name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Confirm Password</label>
                            <input type="password" name="confirm_password" class="form-control" required>
                        </div>
                    </div>
                    <div class="mt-4">
                        <button type="submit" class="btn btn-gold">Add User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="card bg-dark text-light border-secondary slide-up" style="animation-delay: 0.2s;">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-dark table-striped table-hover mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($adminUsers as $user): ?>
                        <tr>
                            <td><?= htmlspecialchars($user['id']) ?></td>
                            <td><?= htmlspecialchars($user['full_name']) ?></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td><?= htmlspecialchars(date('M j, Y g:i A', strtotime($user['created_at']))) ?></td>
                            <td>
                                <a href="?edit=<?= $user['id'] ?>" class="btn btn-sm btn-gold-outline"><i class="bi bi-pencil"></i> Edit</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($adminUsers)): ?>
                        <tr><td colspan="5" class="text-center text-muted py-3">No admin users found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
