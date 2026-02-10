<div class="sidebar">
    <div class="sidebar-header">
        <h2>Mabini Inventory</h2>
        <p>Management System</p>
    </div>
    <div class="sidebar-menu">
        <a href="dashboard.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
            <span class="menu-icon">📊</span>
            <span>Dashboard</span>
        </a>
        <a href="products.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'products.php' ? 'active' : ''; ?>">
            <span class="menu-icon">📦</span>
            <span>Product</span>
        </a>
        <a href="cost.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'cost.php' ? 'active' : ''; ?>">
            <span class="menu-icon">💰</span>
            <span>Cost per Unit</span>
        </a>
        <a href="quantity-list.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'quantity-list.php' ? 'active' : ''; ?>">
            <span class="menu-icon">📋</span>
            <span>Quantity List</span>
        </a>
        <a href="quantity-issued.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'quantity-issued.php' ? 'active' : ''; ?>">
            <span class="menu-icon">📤</span>
            <span>Quantity Issued</span>
        </a>
    </div>
</div>
