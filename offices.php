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
    } elseif ($action === 'edit_office') {
        $id = $_POST['id'] ?? 0;
        $new_name = trim($_POST['office_name'] ?? '');
        if (!empty($new_name)) {
            foreach ($_SESSION['offices'] as $key => $office) {
                if ($office['id'] == $id) {
                    $_SESSION['offices'][$key]['office_name'] = $new_name;
                    break;
                }
            }
        }
        $_SESSION['success_message'] = 'Office updated successfully!';
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
                                        <div class="dropdown-item" data-office-name="<?php echo strtolower(htmlspecialchars($office['office_name'])); ?>" onclick="showOfficeDetails(<?php echo $index; ?>)">
                                            <?php echo htmlspecialchars($office['office_name']); ?>
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
        
        function toggleEditMode() {
            editMode = !editMode;
            const editBtn = document.getElementById('editModeBtn');
            const normalView = document.getElementById('normalView');
            const editView = document.getElementById('editView');
            
            if (editMode) {
                editBtn.textContent = '✓ Done';
                editBtn.classList.add('active');
                normalView.style.display = 'none';
                editView.style.display = 'block';
                // Auto-open the dropdown
                setTimeout(() => {
                    const selectElement = document.getElementById('editOfficeSelect');
                    if (selectElement) {
                        selectElement.focus();
                        selectElement.click();
                        // For some browsers, we need to trigger it differently
                        const event = new MouseEvent('mousedown', {
                            bubbles: true,
                            cancelable: true,
                            view: window
                        });
                        selectElement.dispatchEvent(event);
                    }
                }, 100);
            } else {
                editBtn.textContent = '✏️ Edit';
                editBtn.classList.remove('active');
                normalView.style.display = 'block';
                editView.style.display = 'none';
            }
        }
        
        function editOffice(officeName, officeId) {
            document.getElementById('edit_office_id').value = officeId;
            document.getElementById('edit_office_name').value = officeName;
            showModal('editOfficeModal');
        }
        
        function handleEditSelect(selectElement) {
            const selectedOption = selectElement.options[selectElement.selectedIndex];
            if (selectedOption.value) {
                const officeId = selectedOption.value;
                const officeName = selectedOption.getAttribute('data-name');
                editOffice(officeName, officeId);
                // Reset dropdown
                selectElement.selectedIndex = 0;
            }
        }
        
        function handleOfficeClick(index, officeName, officeId) {
            if (editMode) {
                // In edit mode, open edit modal
                document.getElementById('edit_office_id').value = officeId;
                document.getElementById('edit_office_name').value = officeName;
                showModal('editOfficeModal');
            } else {
                // In normal mode, show office details
                showOfficeDetails(index);
            }
        }
        
        function deleteOfficeFromModal() {
            const officeId = document.getElementById('edit_office_id').value;
            if (confirm('Are you sure you want to delete this office?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_office">
                    <input type="hidden" name="id" value="${officeId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function toggleDropdown() {
            document.getElementById('officesDropdown').classList.toggle('show');
        }
        
        function filterOffices() {
            const searchInput = document.getElementById('officeSearch');
            const filter = searchInput.value.toLowerCase();
            const dropdown = document.getElementById('officesDropdown');
            const items = dropdown.getElementsByClassName('dropdown-item');
            
            let visibleCount = 0;
            
            for (let i = 0; i < items.length; i++) {
                const officeName = items[i].getAttribute('data-office-name');
                if (officeName && officeName.includes(filter)) {
                    items[i].style.display = 'block';
                    visibleCount++;
                } else if (items[i].classList.contains('empty')) {
                    items[i].style.display = 'block';
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
        .button-group {
            display: flex;
            gap: 10px;
        }
        
        .btn-secondary {
            padding: 10px 20px;
            background-color: #f44336;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-secondary:hover {
            background-color: #d32f2f;
        }
        
        .btn-secondary.active {
            background-color: #4CAF50;
        }
        
        .btn-danger {
            padding: 10px 20px;
            background-color: #f44336;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-danger:hover {
            background-color: #d32f2f;
        }
        
        .modal-buttons {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }
        
        .modal-buttons button {
            flex: 1;
        }
        
        .edit-view-container {
            margin-top: 20px;
        }
        
        .edit-view-container h3 {
            color: #666;
            margin-bottom: 20px;
            font-size: 18px;
        }
        
        .edit-dropdown {
            max-width: 500px;
        }
        
        .edit-select {
            width: 100%;
            padding: 15px 20px;
            font-size: 16px;
            font-family: 'Poppins', sans-serif;
            border: 2px solid #ddd;
            border-radius: 8px;
            background-color: white;
            cursor: pointer;
            outline: none;
            transition: all 0.3s ease;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23333' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 15px center;
            padding-right: 40px;
        }
        
        .edit-select:hover {
            border-color: #4CAF50;
        }
        
        .edit-select:focus {
            border-color: #4CAF50;
            box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.1);
        }
        
        .edit-select option {
            padding: 10px;
        }
        
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
            display: block;
            width: 100%;
        }
        
        .dropdown-item.empty:hover {
            background-color: transparent;
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
