<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <h2>Mabini Inventory</h2>
        <p>Management System</p>
    </div>
    <div class="sidebar-menu">
        <a href="dashboard.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
            <span class="menu-icon">📊</span>
            <span class="menu-text">Dashboard</span>
        </a>
        
        <div class="menu-section">
            <div class="menu-section-title">Inventory</div>
            <a href="item-master-list.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'item-master-list.php' ? 'active' : ''; ?>">
                <span class="menu-icon">📦</span>
                <span class="menu-text">Item Master List</span>
            </a>
            <a href="reorder-management.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'reorder-management.php' ? 'active' : ''; ?>">
                <span class="menu-icon">🔄</span>
                <span class="menu-text">Reorder Management</span>
            </a>
        </div>
        
        <div class="menu-section">
            <div class="menu-section-title">Transactions</div>
            <a href="issue-items.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'issue-items.php' ? 'active' : ''; ?>">
                <span class="menu-icon">📤</span>
                <span class="menu-text">Issue Items</span>
            </a>
            <a href="receive-items.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'receive-items.php' ? 'active' : ''; ?>">
                <span class="menu-icon">📥</span>
                <span class="menu-text">Receive Items</span>
            </a>
            <a href="process-transactions.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'process-transactions.php' ? 'active' : ''; ?>">
                <span class="menu-icon">⚙️</span>
                <span class="menu-text">Process Transactions</span>
            </a>
        </div>
        
        <div class="menu-section">
            <div class="menu-section-title">Setup</div>
            <a href="offices.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'offices.php' ? 'active' : ''; ?>">
                <span class="menu-icon">🏢</span>
                <span class="menu-text">Offices</span>
            </a>
            <a href="report.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'report.php' ? 'active' : ''; ?>">
                <span class="menu-icon">📄</span>
                <span class="menu-text">Report</span>
            </a>
        </div>
    </div>
</div>

<style>
.menu-section {
    margin: 20px 0;
}

.menu-section-title {
    color: rgba(255, 255, 255, 0.5);
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1px;
    padding: 10px 20px 5px 20px;
    margin-top: 15px;
}

.menu-item {
    margin: 2px 10px;
}
</style>

<script>
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.querySelector('.main-content');
    const hamburger = document.querySelector('.hamburger-menu');
    
    sidebar.classList.toggle('collapsed');
    mainContent.classList.toggle('sidebar-collapsed');
    hamburger.classList.toggle('active');
}
</script>
