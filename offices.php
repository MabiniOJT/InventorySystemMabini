<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

// Initialize session with predefined offices (only if not already set)
if (!isset($_SESSION['offices'])) {
    $_SESSION['offices'] = [
        ['id' => 1, 'office_name' => 'M.O', 'created_at' => date('Y-m-d H:i:s')],
        ['id' => 2, 'office_name' => 'V.M.O', 'created_at' => date('Y-m-d H:i:s')],
        ['id' => 3, 'office_name' => 'HRMO', 'created_at' => date('Y-m-d H:i:s')],
        ['id' => 4, 'office_name' => 'MPDC', 'created_at' => date('Y-m-d H:i:s')],
        ['id' => 5, 'office_name' => 'LCR', 'created_at' => date('Y-m-d H:i:s')],
        ['id' => 6, 'office_name' => 'MBO', 'created_at' => date('Y-m-d H:i:s')],
        ['id' => 7, 'office_name' => 'ACCOUNTING', 'created_at' => date('Y-m-d H:i:s')],
        ['id' => 8, 'office_name' => 'MTO', 'created_at' => date('Y-m-d H:i:s')],
        ['id' => 9, 'office_name' => 'ASSESSOR', 'created_at' => date('Y-m-d H:i:s')],
        ['id' => 10, 'office_name' => 'LIBRARY', 'created_at' => date('Y-m-d H:i:s')],
        ['id' => 11, 'office_name' => 'RHU', 'created_at' => date('Y-m-d H:i:s')],
        ['id' => 12, 'office_name' => 'MSWD', 'created_at' => date('Y-m-d H:i:s')],
        ['id' => 13, 'office_name' => 'AGRI', 'created_at' => date('Y-m-d H:i:s')],
        ['id' => 14, 'office_name' => 'ENGINEERING', 'created_at' => date('Y-m-d H:i:s')],
        ['id' => 15, 'office_name' => 'MARKET', 'created_at' => date('Y-m-d H:i:s')],
        ['id' => 16, 'office_name' => 'MDR', 'created_at' => date('Y-m-d H:i:s')],
        ['id' => 17, 'office_name' => 'R.S.I', 'created_at' => date('Y-m-d H:i:s')],
        ['id' => 18, 'office_name' => 'DENTAL', 'created_at' => date('Y-m-d H:i:s')],
        ['id' => 19, 'office_name' => 'M.I', 'created_at' => date('Y-m-d H:i:s')],
        ['id' => 20, 'office_name' => 'NUTRITION', 'created_at' => date('Y-m-d H:i:s')],
        ['id' => 21, 'office_name' => 'MOTORPOOL', 'created_at' => date('Y-m-d H:i:s')],
        ['id' => 22, 'office_name' => 'DILG', 'created_at' => date('Y-m-d H:i:s')],
        ['id' => 23, 'office_name' => 'OSCA', 'created_at' => date('Y-m-d H:i:s')],
        ['id' => 24, 'office_name' => 'BAWASA', 'created_at' => date('Y-m-d H:i:s')],
        ['id' => 25, 'office_name' => 'BPLO', 'created_at' => date('Y-m-d H:i:s')],
        ['id' => 26, 'office_name' => 'MIDWIFE', 'created_at' => date('Y-m-d H:i:s')],
        ['id' => 27, 'office_name' => 'LEGAL OFFICE', 'created_at' => date('Y-m-d H:i:s')],
        ['id' => 28, 'office_name' => 'GSO', 'created_at' => date('Y-m-d H:i:s')]
    ];
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_office') {
        $office_name = trim($_POST['office_name'] ?? '');
        if (!empty($office_name)) {
            // Get the highest ID
            $maxId = 0;
            foreach ($_SESSION['offices'] as $office) {
                if ($office['id'] > $maxId) {
                    $maxId = $office['id'];
                }
            }
            $_SESSION['offices'][] = [
                'id' => $maxId + 1,
                'office_name' => $office_name,
                'created_at' => date('Y-m-d H:i:s')
            ];
        }
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
                    <div class="offices-header">
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
                                        <div class="dropdown-item-container" data-office-name="<?php echo strtolower(htmlspecialchars($office['office_name'])); ?>">
                                            <div class="dropdown-item" onclick="showOfficeDetails(<?php echo $index; ?>)">
                                                <?php echo htmlspecialchars($office['office_name']); ?>
                                            </div>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this office?');">
                                                <input type="hidden" name="action" value="delete_office">
                                                <input type="hidden" name="id" value="<?php echo $office['id']; ?>">
                                                <button type="submit" class="delete-btn" title="Delete Office">×</button>
                                            </form>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="search-container">
                            <input type="text" id="officeSearch" class="search-input" placeholder="🔍 Search offices..." onkeyup="filterOffices()">
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
                    <input type="text" name="office_name" placeholder="Enter office name" required autofocus>
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
        
        function filterOffices() {
            const searchInput = document.getElementById('officeSearch');
            const filter = searchInput.value.toLowerCase();
            const dropdown = document.getElementById('officesDropdown');
            const items = dropdown.getElementsByClassName('dropdown-item-container');
            
            let visibleCount = 0;
            
            for (let i = 0; i < items.length; i++) {
                const officeName = items[i].getAttribute('data-office-name');
                if (officeName && officeName.includes(filter)) {
                    items[i].style.display = 'flex';
                    visibleCount++;
                } else {
                    items[i].style.display = 'none';
                }
            }
            
            // Auto-open dropdown when searching
            if (filter.length > 0 && visibleCount > 0) {
                dropdown.classList.add('show');
            }
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
                        <strong>Office ID:</strong> ${office.id}
                    </div>
                    <div class="detail-item">
                        <strong>Created:</strong> ${office.created_at}
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
        
        .offices-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .search-container {
            flex: 0 0 auto;
        }
        
        .search-input {
            width: 400px;
            padding: 12px 20px;
            font-size: 16px;
            border: 2px solid #ddd;
            border-radius: 8px;
            outline: none;
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
        }
        
        .search-input:focus {
            border-color: #4CAF50;
            box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.1);
        }
        
        .search-input::placeholder {
            color: #999;
        }
        
        .dropdown {
            position: relative;
            display: inline-block;
            width: auto;
            min-width: 400px;
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
        
        .dropdown-item-container {
            display: flex;
            align-items: center;
            border-bottom: 1px solid #f0f0f0;
            background: white;
            transition: background 0.2s ease;
        }
        
        .dropdown-item-container:hover {
            background-color: #f1f1f1;
        }
        
        .dropdown-item {
            color: #333;
            padding: 14px 20px;
            text-decoration: none;
            flex: 1;
            cursor: pointer;
            transition: all 0.2s ease;
            font-weight: 500;
        }
        
        .delete-btn {
            background: #f44336;
            color: white;
            border: none;
            padding: 8px 12px;
            margin-right: 8px;
            cursor: pointer;
            border-radius: 4px;
            font-size: 20px;
            font-weight: bold;
            line-height: 1;
            transition: background 0.2s ease;
        }
        
        .delete-btn:hover {
            background: #d32f2f;
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
