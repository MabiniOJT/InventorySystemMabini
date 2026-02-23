<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

// Initialize session arrays with predefined offices - FORCE RESET
$_SESSION['offices'] = [
    ['id' => 1, 'office_name' => 'M.O', 'office_code' => 'MO', 'department' => '', 'contact_person' => '', 'contact_number' => '', 'email' => '', 'created_at' => date('Y-m-d H:i:s')],
    ['id' => 2, 'office_name' => 'V.M.O', 'office_code' => 'VMO', 'department' => '', 'contact_person' => '', 'contact_number' => '', 'email' => '', 'created_at' => date('Y-m-d H:i:s')],
    ['id' => 3, 'office_name' => 'HRMO', 'office_code' => 'HRMO', 'department' => '', 'contact_person' => '', 'contact_number' => '', 'email' => '', 'created_at' => date('Y-m-d H:i:s')],
    ['id' => 4, 'office_name' => 'MPDC', 'office_code' => 'MPDC', 'department' => '', 'contact_person' => '', 'contact_number' => '', 'email' => '', 'created_at' => date('Y-m-d H:i:s')],
    ['id' => 5, 'office_name' => 'LCR', 'office_code' => 'LCR', 'department' => '', 'contact_person' => '', 'contact_number' => '', 'email' => '', 'created_at' => date('Y-m-d H:i:s')],
    ['id' => 6, 'office_name' => 'MBO', 'office_code' => 'MBO', 'department' => '', 'contact_person' => '', 'contact_number' => '', 'email' => '', 'created_at' => date('Y-m-d H:i:s')],
    ['id' => 7, 'office_name' => 'ACCOUNTING', 'office_code' => 'ACCT', 'department' => '', 'contact_person' => '', 'contact_number' => '', 'email' => '', 'created_at' => date('Y-m-d H:i:s')],
    ['id' => 8, 'office_name' => 'MTO', 'office_code' => 'MTO', 'department' => '', 'contact_person' => '', 'contact_number' => '', 'email' => '', 'created_at' => date('Y-m-d H:i:s')],
    ['id' => 9, 'office_name' => 'ASSESSOR', 'office_code' => 'ASSR', 'department' => '', 'contact_person' => '', 'contact_number' => '', 'email' => '', 'created_at' => date('Y-m-d H:i:s')],
    ['id' => 10, 'office_name' => 'LIBRARY', 'office_code' => 'LIB', 'department' => '', 'contact_person' => '', 'contact_number' => '', 'email' => '', 'created_at' => date('Y-m-d H:i:s')],
    ['id' => 11, 'office_name' => 'RHU', 'office_code' => 'RHU', 'department' => '', 'contact_person' => '', 'contact_number' => '', 'email' => '', 'created_at' => date('Y-m-d H:i:s')],
    ['id' => 12, 'office_name' => 'MSWD', 'office_code' => 'MSWD', 'department' => '', 'contact_person' => '', 'contact_number' => '', 'email' => '', 'created_at' => date('Y-m-d H:i:s')],
    ['id' => 13, 'office_name' => 'AGRI', 'office_code' => 'AGRI', 'department' => '', 'contact_person' => '', 'contact_number' => '', 'email' => '', 'created_at' => date('Y-m-d H:i:s')],
    ['id' => 14, 'office_name' => 'ENGINEERING', 'office_code' => 'ENG', 'department' => '', 'contact_person' => '', 'contact_number' => '', 'email' => '', 'created_at' => date('Y-m-d H:i:s')],
    ['id' => 15, 'office_name' => 'MARKET', 'office_code' => 'MKT', 'department' => '', 'contact_person' => '', 'contact_number' => '', 'email' => '', 'created_at' => date('Y-m-d H:i:s')],
    ['id' => 16, 'office_name' => 'MDR', 'office_code' => 'MDR', 'department' => '', 'contact_person' => '', 'contact_number' => '', 'email' => '', 'created_at' => date('Y-m-d H:i:s')],
    ['id' => 17, 'office_name' => 'R.S.I', 'office_code' => 'RSI', 'department' => '', 'contact_person' => '', 'contact_number' => '', 'email' => '', 'created_at' => date('Y-m-d H:i:s')],
    ['id' => 18, 'office_name' => 'DENTAL', 'office_code' => 'DNTL', 'department' => '', 'contact_person' => '', 'contact_number' => '', 'email' => '', 'created_at' => date('Y-m-d H:i:s')],
    ['id' => 19, 'office_name' => 'M.I', 'office_code' => 'MI', 'department' => '', 'contact_person' => '', 'contact_number' => '', 'email' => '', 'created_at' => date('Y-m-d H:i:s')],
    ['id' => 20, 'office_name' => 'NUTRITION', 'office_code' => 'NUTR', 'department' => '', 'contact_person' => '', 'contact_number' => '', 'email' => '', 'created_at' => date('Y-m-d H:i:s')],
    ['id' => 21, 'office_name' => 'MOTORPOOL', 'office_code' => 'MTRPL', 'department' => '', 'contact_person' => '', 'contact_number' => '', 'email' => '', 'created_at' => date('Y-m-d H:i:s')],
    ['id' => 22, 'office_name' => 'DILG', 'office_code' => 'DILG', 'department' => '', 'contact_person' => '', 'contact_number' => '', 'email' => '', 'created_at' => date('Y-m-d H:i:s')],
    ['id' => 23, 'office_name' => 'OSCA', 'office_code' => 'OSCA', 'department' => '', 'contact_person' => '', 'contact_number' => '', 'email' => '', 'created_at' => date('Y-m-d H:i:s')],
    ['id' => 24, 'office_name' => 'BAWASA', 'office_code' => 'BWSA', 'department' => '', 'contact_person' => '', 'contact_number' => '', 'email' => '', 'created_at' => date('Y-m-d H:i:s')],
    ['id' => 25, 'office_name' => 'BPLO', 'office_code' => 'BPLO', 'department' => '', 'contact_person' => '', 'contact_number' => '', 'email' => '', 'created_at' => date('Y-m-d H:i:s')],
    ['id' => 26, 'office_name' => 'MIDWIFE', 'office_code' => 'MDWF', 'department' => '', 'contact_person' => '', 'contact_number' => '', 'email' => '', 'created_at' => date('Y-m-d H:i:s')],
    ['id' => 27, 'office_name' => 'LEGAL OFFICE', 'office_code' => 'LEGAL', 'department' => '', 'contact_person' => '', 'contact_number' => '', 'email' => '', 'created_at' => date('Y-m-d H:i:s')],
    ['id' => 28, 'office_name' => 'GSO', 'office_code' => 'GSO', 'department' => '', 'contact_person' => '', 'contact_number' => '', 'email' => '', 'created_at' => date('Y-m-d H:i:s')]
];

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
                
                <div class="offices-dropdown-container">
                    <div class="dropdown">
                        <button class="dropdown-btn" onclick="toggleDropdown()">
                            OFFICES
                            <span class="dropdown-arrow">▼</span>
                        </button>
                        <div id="officesDropdown" class="dropdown-content">
                            <?php if (empty($offices)): ?>
                                <div class="dropdown-item empty">No offices found</div>
                            <?php else: ?>
                                <?php foreach ($offices as $index => $office): ?>
                                    <div class="dropdown-item" onclick="showOfficeDetails(<?php echo $index; ?>)">
                                        <?php echo htmlspecialchars($office['office_name']); ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div id="officeDetailsPanel" class="office-details-panel" style="display: none;">
                        <h3 id="selectedOfficeName"></h3>
                        <div id="officeDetailsContent"></div>
                    </div>
                </div>
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
        const offices = <?php echo json_encode($offices); ?>;
        
        function showModal(modalId) {
            document.getElementById(modalId).classList.add('show');
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('show');
        }
        
        function toggleDropdown() {
            document.getElementById('officesDropdown').classList.toggle('show');
        }
        
        function showOfficeDetails(index) {
            const office = offices[index];
            const panel = document.getElementById('officeDetailsPanel');
            const nameElement = document.getElementById('selectedOfficeName');
            const contentElement = document.getElementById('officeDetailsContent');
            
            nameElement.textContent = office.office_name;
            contentElement.innerHTML = `
                <div class="details-grid">
                    <div class="detail-item">
                        <strong>ID:</strong> ${office.id}
                    </div>
                    <div class="detail-item">
                        <strong>Office Code:</strong> ${office.office_code || 'N/A'}
                    </div>
                    <div class="detail-item">
                        <strong>Department:</strong> ${office.department || 'N/A'}
                    </div>
                    <div class="detail-item">
                        <strong>Contact Person:</strong> ${office.contact_person || 'N/A'}
                    </div>
                    <div class="detail-item">
                        <strong>Contact Number:</strong> ${office.contact_number || 'N/A'}
                    </div>
                    <div class="detail-item">
                        <strong>Email:</strong> ${office.email || 'N/A'}
                    </div>
                </div>
            `;
            
            panel.style.display = 'block';
            document.getElementById('officesDropdown').classList.remove('show');
        }
        
        // Close dropdown when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.classList.remove('show');
            }
            if (!event.target.matches('.dropdown-btn')) {
                const dropdown = document.getElementById('officesDropdown');
                if (dropdown && dropdown.classList.contains('show')) {
                    dropdown.classList.remove('show');
                }
            }
        }
    </script>
    
    <style>
        .offices-dropdown-container {
            margin-top: 20px;
        }
        
        .dropdown {
            position: relative;
            display: inline-block;
            width: 100%;
            max-width: 400px;
        }
        
        .dropdown-btn {
            background-color: #4CAF50;
            color: white;
            padding: 16px 20px;
            font-size: 18px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            width: 100%;
            text-align: left;
            border-radius: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            transition: all 0.3s ease;
        }
        
        .dropdown-btn:hover {
            background-color: #45a049;
            box-shadow: 0 4px 8px rgba(0,0,0,0.3);
        }
        
        .dropdown-arrow {
            transition: transform 0.3s ease;
        }
        
        .dropdown-content {
            display: none;
            position: absolute;
            background-color: #fff;
            width: 100%;
            max-height: 400px;
            overflow-y: auto;
            box-shadow: 0 8px 16px rgba(0,0,0,0.2);
            z-index: 1000;
            border-radius: 8px;
            margin-top: 5px;
        }
        
        .dropdown-content.show {
            display: block;
        }
        
        .dropdown-item {
            color: #333;
            padding: 14px 20px;
            text-decoration: none;
            display: block;
            cursor: pointer;
            border-bottom: 1px solid #f0f0f0;
            transition: all 0.2s ease;
            font-weight: 500;
        }
        
        .dropdown-item:hover {
            background-color: #f1f1f1;
            padding-left: 25px;
        }
        
        .dropdown-item.empty {
            color: #999;
            cursor: default;
        }
        
        .dropdown-item.empty:hover {
            background-color: transparent;
            padding-left: 20px;
        }
        
        .office-details-panel {
            margin-top: 30px;
            padding: 25px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-left: 4px solid #4CAF50;
        }
        
        .office-details-panel h3 {
            color: #4CAF50;
            margin-top: 0;
            margin-bottom: 20px;
            font-size: 24px;
        }
        
        .details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
        }
        
        .detail-item {
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .detail-item strong {
            color: #4CAF50;
            display: block;
            margin-bottom: 5px;
            font-size: 14px;
        }
    </style>
</body>
</html>
