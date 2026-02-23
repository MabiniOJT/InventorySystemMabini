<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

// Initialize session arrays
if (!isset($_SESSION['offices'])) $_SESSION['offices'] = [];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_office') {
        $_SESSION['offices'][] = [
            'id' => count($_SESSION['offices']) + 1,
            'office_name' => $_POST['office_name'] ?? '',
            'office_code' => $_POST['office_code'] ?? '',
            'department' => $_POST['department'] ?? '',
            'contact_person' => $_POST['contact_person'] ?? '',
            'contact_number' => $_POST['contact_number'] ?? '',
            'email' => $_POST['email'] ?? '',
            'created_at' => date('Y-m-d H:i:s')
        ];
        $_SESSION['success_message'] = 'Office added successfully!';
        header('Location: offices.php');
        exit;
    } elseif ($action === 'delete_office') {
        $id = $_POST['id'] ?? 0;
        foreach ($_SESSION['offices'] as $key => $office) {
            if ($office['id'] == $id) {
                unset($_SESSION['offices'][$key]);
                $_SESSION['offices'] = array_values($_SESSION['offices']);
                break;
            }
        }
        $_SESSION['success_message'] = 'Office deleted successfully!';
        header('Location: offices.php');
        exit;
    }
}

$user = $_SESSION['user'];
$offices = $_SESSION['offices'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Offices - Mabini Inventory System</title>
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
                    <h2>Offices Management</h2>
                    <button class="btn-primary" onclick="showModal('officeModal')">+ Add Office</button>
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
                            <th>Office Name</th>
                            <th>Office Code</th>
                            <th>Department</th>
                            <th>Contact Person</th>
                            <th>Contact Number</th>
                            <th>Email</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($offices)): ?>
                        <tr>
                            <td colspan="8">
                                <div class="empty-state">
                                    <p>No offices found. Click "Add Office" to get started.</p>
                                </div>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($offices as $office): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($office['id']); ?></td>
                                    <td><?php echo htmlspecialchars($office['office_name']); ?></td>
                                    <td><?php echo htmlspecialchars($office['office_code'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($office['department'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($office['contact_person'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($office['contact_number'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($office['email'] ?? ''); ?></td>
                                    <td>
                                        <button class="btn-action btn-edit">Edit</button>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="delete_office">
                                            <input type="hidden" name="id" value="<?php echo $office['id']; ?>">
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
    
    <!-- Office Modal -->
    <div id="officeModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('officeModal')">&times;</span>
            <h2>Add New Office</h2>
            <form method="POST">
                <input type="hidden" name="action" value="add_office">
                <div class="form-group">
                    <label>Office Name *</label>
                    <input type="text" name="office_name" required>
                </div>
                <div class="form-group">
                    <label>Office Code *</label>
                    <input type="text" name="office_code" required>
                </div>
                <div class="form-group">
                    <label>Department</label>
                    <input type="text" name="department">
                </div>
                <div class="form-group">
                    <label>Contact Person</label>
                    <input type="text" name="contact_person">
                </div>
                <div class="form-group">
                    <label>Contact Number</label>
                    <input type="text" name="contact_number">
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email">
                </div>
                <button type="submit" class="btn-primary">Add Office</button>
            </form>
        </div>
    </div>

    <script>
        function showModal(modalId) {
            document.getElementById(modalId).classList.add('show');
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('show');
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.classList.remove('show');
            }
        }
    </script>
</body>
</html>
