<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

// Initialize session arrays
if (!isset($_SESSION['costs'])) $_SESSION['costs'] = [];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_cost') {
        $_SESSION['costs'][] = [
            'id' => count($_SESSION['costs']) + 1,
            'product_name' => $_POST['product_name'] ?? '',
            'unit_cost' => $_POST['unit_cost'] ?? '',
            'updated_at' => date('Y-m-d H:i:s')
        ];
        $_SESSION['success_message'] = 'Cost added successfully!';
        header('Location: cost.php');
        exit;
    } elseif ($action === 'delete_cost') {
        $id = $_POST['id'] ?? 0;
        foreach ($_SESSION['costs'] as $key => $cost) {
            if ($cost['id'] == $id) {
                unset($_SESSION['costs'][$key]);
                $_SESSION['costs'] = array_values($_SESSION['costs']);
                break;
            }
        }
        $_SESSION['success_message'] = 'Cost deleted successfully!';
        header('Location: cost.php');
        exit;
    }
}

$user = $_SESSION['user'];
$costs = $_SESSION['costs'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cost per Unit - Mabini Inventory System</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <?php include 'includes/navbar.php'; ?>

        <div class="content">
            <div class="content-section active">
                <div class="section-header">
                    <h2>Cost per Unit</h2>
                    <button class="btn-primary" onclick="showModal('costModal')">+ Add Cost</button>
                </div>
                
                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success">
                        <?php echo htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?>
                    </div>
                <?php endif; ?>
                
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Product Name</th>
                            <th>Unit Cost</th>
                            <th>Last Updated</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($costs)): ?>
                        <tr>
                            <td colspan="5">
                                <div class="empty-state">
                                    <p>No cost data available. Click "Add Cost" to get started.</p>
                                </div>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($costs as $cost): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($cost['id']); ?></td>
                                    <td><?php echo htmlspecialchars($cost['product_name']); ?></td>
                                    <td>₱<?php echo number_format($cost['unit_cost'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($cost['updated_at']); ?></td>
                                    <td>
                                        <button class="btn-action btn-edit">Edit</button>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="delete_cost">
                                            <input type="hidden" name="id" value="<?php echo $cost['id']; ?>">
                                            <button type="submit" class="btn-action btn-delete" onclick="return confirm('Are you sure?')">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Cost Modal -->
    <div id="costModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('costModal')">&times;</span>
            <h2>Add Cost per Unit</h2>
            <form method="POST">
                <input type="hidden" name="action" value="add_cost">
                <div class="form-group">
                    <label>Product Name</label>
                    <input type="text" name="product_name" required>
                </div>
                <div class="form-group">
                    <label>Unit Cost (₱)</label>
                    <input type="number" step="0.01" name="unit_cost" required>
                </div>
                <button type="submit" class="btn-primary">Add Cost</button>
            </form>
        </div>
    </div>

    <script src="script.js"></script>
</body>
</html>
