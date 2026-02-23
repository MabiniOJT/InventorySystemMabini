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
        <a href="item-master-list.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'item-master-list.php' ? 'active' : ''; ?>">
            <span class="menu-icon">📦</span>
            <span class="menu-text">Item Master List</span>
        </a>
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
