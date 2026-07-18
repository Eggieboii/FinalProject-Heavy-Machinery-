<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdmin();

$pageTitle = 'Manage Products';
$errors = [];
$success = '';

// Check edit mode
$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$editProduct = null;

if ($editId > 0) {
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param("i", $editId);
    $stmt->execute();
    $editProduct = $stmt->get_result()->fetch_assoc();
}

// Fetch categories for dropdown
$categoriesStmt = $conn->prepare("SELECT id, name FROM categories ORDER BY name ASC");
$categoriesStmt->execute();
$categories = $categoriesStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Handle Add/Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    $name = sanitize($_POST['name'] ?? '');
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $description = sanitize($_POST['description'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $stockQuantity = (int)($_POST['stock_quantity'] ?? 0);
    $status = sanitize($_POST['status'] ?? 'active');

    if ($action === 'add_product') {
        if (empty($name) || $categoryId <= 0 || $price <= 0) {
            $errors[] = "Name, valid category, and valid price are required.";
        } else {
            $imageName = 'default.jpg';
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $fileInfo = pathinfo($_FILES['image']['name']);
                $ext = strtolower($fileInfo['extension']);
                $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                if (in_array($ext, $allowed) && $_FILES['image']['size'] <= 5242880) { // 5MB limit
                    $imageName = uniqid('prod_') . '.' . $ext;
                    move_uploaded_file($_FILES['image']['tmp_name'], UPLOAD_DIR . '/' . $imageName);
                } else {
                    $errors[] = "Invalid image or size > 5MB.";
                }
            }

            if (empty($errors)) {
                $stmt = $conn->prepare("INSERT INTO products (category_id, name, description, price, stock_quantity, image, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
                $stmt->bind_param("issdiss", $categoryId, $name, $description, $price, $stockQuantity, $imageName, $status);
                if ($stmt->execute()) {
                    logAudit($conn, getCurrentUserId(), "Added new product: $name");
                    $_SESSION['success_msg'] = "Product added successfully.";
                    redirect('admin/products.php');
                } else {
                    $errors[] = "Failed to add product.";
                }
            }
        }
    } elseif ($action === 'update_product' && $editId > 0) {
        if (empty($name) || $categoryId <= 0 || $price <= 0) {
            $errors[] = "Name, valid category, and valid price are required.";
        } else {
            $imageName = $editProduct['image'];
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $fileInfo = pathinfo($_FILES['image']['name']);
                $ext = strtolower($fileInfo['extension']);
                $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                if (in_array($ext, $allowed) && $_FILES['image']['size'] <= 5242880) {
                    $newImageName = uniqid('prod_') . '.' . $ext;
                    if (move_uploaded_file($_FILES['image']['tmp_name'], UPLOAD_DIR . '/' . $newImageName)) {
                        // Delete old if not default
                        if ($imageName && $imageName !== 'default.jpg' && file_exists(UPLOAD_DIR . '/' . $imageName)) {
                            unlink(UPLOAD_DIR . '/' . $imageName);
                        }
                        $imageName = $newImageName;
                    }
                } else {
                    $errors[] = "Invalid image or size > 5MB.";
                }
            }

            if (empty($errors)) {
                $stmt = $conn->prepare("UPDATE products SET category_id = ?, name = ?, description = ?, price = ?, stock_quantity = ?, image = ?, status = ?, updated_at = NOW() WHERE id = ?");
                $stmt->bind_param("issdissi", $categoryId, $name, $description, $price, $stockQuantity, $imageName, $status, $editId);
                if ($stmt->execute()) {
                    logAudit($conn, getCurrentUserId(), "Modified product: $name");
                    $_SESSION['success_msg'] = "Product updated successfully.";
                    redirect('admin/products.php');
                } else {
                    $errors[] = "Failed to update product.";
                }
            }
        }
    }
}

if (isset($_SESSION['success_msg'])) {
    $success = $_SESSION['success_msg'];
    unset($_SESSION['success_msg']);
}

// Fetch all products
$stmt = $conn->prepare("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.created_at DESC");
$stmt->execute();
$products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container mt-4 fade-in">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="section-title text-gold m-0">Manage Products</h2>
        <?php if (!$editProduct): ?>
        <button class="btn btn-gold" type="button" data-bs-toggle="collapse" data-bs-target="#addProductForm" aria-expanded="false" aria-controls="addProductForm">
            <i class="bi bi-plus-circle me-1"></i> Add Product
        </button>
        <?php endif; ?>
    </div>

    <?= displayMessages($errors, $success) ?>

    <?php if ($editProduct): ?>
    <div class="card bg-dark text-light border-secondary mb-4 slide-up">
        <div class="card-header border-secondary text-gold">Edit Product: <?= htmlspecialchars($editProduct['name']) ?></div>
        <div class="card-body">
            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="action" value="update_product">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($editProduct['name']) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Category</label>
                        <select name="category_id" class="form-control" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= $editProduct['category_id'] == $cat['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Price</label>
                        <input type="number" step="0.01" name="price" class="form-control" value="<?= htmlspecialchars($editProduct['price']) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Stock Quantity</label>
                        <input type="number" name="stock_quantity" class="form-control" value="<?= htmlspecialchars($editProduct['stock_quantity']) ?>" required>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($editProduct['description']) ?></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-control">
                            <option value="active" <?= $editProduct['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= $editProduct['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Image (Leave empty to keep current)</label>
                        <input type="file" name="image" class="form-control" accept="image/*">
                        <?php if($editProduct['image']): ?>
                        <div class="mt-2">
                            <img src="<?= htmlspecialchars(BASE_URL . '/' . PRODUCT_IMG_PATH . '/' . $editProduct['image']) ?>" alt="Current Image" width="50" class="rounded border border-secondary">
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="mt-4">
                    <button type="submit" class="btn btn-gold">Update Product</button>
                    <a href="products.php" class="btn btn-secondary ms-2">Cancel</a>
                </div>
            </form>
        </div>
    </div>
    <?php else: ?>
    <div class="collapse mb-4 slide-up" id="addProductForm">
        <div class="card bg-dark text-light border-secondary">
            <div class="card-header border-secondary text-gold">Add New Product</div>
            <div class="card-body">
                <form method="POST" action="" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add_product">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Name</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Category</label>
                            <select name="category_id" class="form-control" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Price</label>
                            <input type="number" step="0.01" name="price" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Stock Quantity</label>
                            <input type="number" name="stock_quantity" class="form-control" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-control">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Image</label>
                            <input type="file" name="image" class="form-control" accept="image/*">
                        </div>
                    </div>
                    <div class="mt-4">
                        <button type="submit" class="btn btn-gold">Add Product</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="card bg-dark text-light border-secondary slide-up" style="animation-delay: 0.2s;">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-dark table-striped table-hover mb-0 align-middle">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $p): ?>
                        <tr>
                            <td>
                                <?php if($p['image']): ?>
                                <img src="<?= htmlspecialchars(BASE_URL . '/' . PRODUCT_IMG_PATH . '/' . $p['image']) ?>" alt="Img" width="50" height="50" style="object-fit:cover;" class="rounded border border-secondary">
                                <?php else: ?>
                                <div style="width:50px;height:50px;" class="bg-secondary rounded"></div>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($p['name']) ?></td>
                            <td><?= htmlspecialchars($p['category_name']) ?></td>
                            <td><?= htmlspecialchars(formatPrice($p['price'])) ?></td>
                            <td>
                                <?php
                                $stock = (int)$p['stock_quantity'];
                                if($stock > 10) $badge = 'badge-stock-in';
                                elseif($stock > 0) $badge = 'badge-stock-low';
                                else $badge = 'badge-stock-out';
                                ?>
                                <span class="badge <?= $badge ?>"><?= $stock ?></span>
                            </td>
                            <td><span class="badge <?= $p['status'] === 'active' ? 'bg-success' : 'bg-secondary' ?>"><?= ucfirst($p['status']) ?></span></td>
                            <td>
                                <a href="?edit=<?= $p['id'] ?>" class="btn btn-sm btn-gold-outline"><i class="bi bi-pencil"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($products)): ?>
                        <tr><td colspan="7" class="text-center text-muted py-3">No products found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
