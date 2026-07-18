<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdmin();

$pageTitle = 'Audit Log';
logAudit($conn, getCurrentUserId(), 'Viewed Audit Log');

$currentUserId = getCurrentUserId();

$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';

$query = "SELECT * FROM audit_log WHERE user_id = ?";
$params = [$currentUserId];
$types = "i";

if (!empty($dateFrom) && !empty($dateTo)) {
    $query .= " AND created_at BETWEEN ? AND ?";
    $params[] = $dateFrom . ' 00:00:00';
    $params[] = $dateTo . ' 23:59:59';
    $types .= "ss";
}

$query .= " ORDER BY created_at DESC LIMIT 50";

$stmt = $conn->prepare($query);
if(!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$logs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container mt-4 fade-in">
    <h2 class="section-title text-gold mb-4">Audit Log</h2>

    <div class="card bg-dark text-light border-secondary mb-4 slide-up">
        <div class="card-body">
            <form method="GET" action="" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Date From</label>
                    <input type="date" name="date_from" class="form-control" value="<?= htmlspecialchars($dateFrom) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Date To</label>
                    <input type="date" name="date_to" class="form-control" value="<?= htmlspecialchars($dateTo) ?>">
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-gold w-100"><i class="bi bi-filter me-2"></i>Filter</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card bg-dark text-light border-secondary slide-up" style="animation-delay: 0.2s;">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-dark table-striped table-hover mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Action</th>
                            <th>Details</th>
                            <th>IP Address</th>
                            <th>Date & Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?= htmlspecialchars($log['id']) ?></td>
                            <td><?= htmlspecialchars($log['action']) ?></td>
                            <td><?= htmlspecialchars($log['details'] ?? '') ?></td>
                            <td><?= htmlspecialchars($log['ip_address']) ?></td>
                            <td><?= htmlspecialchars(date('M j, Y h:i A', strtotime($log['created_at']))) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($logs)): ?>
                        <tr><td colspan="5" class="text-center text-muted py-3">No audit logs found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
