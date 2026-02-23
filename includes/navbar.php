<nav class="navbar">
    <div style="display: flex; align-items: center; gap: 15px;">
        <button class="hamburger-menu" onclick="toggleSidebar()">
            <span></span>
            <span></span>
            <span></span>
        </button>
        <h1>
            <?php
            $page = basename($_SERVER['PHP_SELF'], '.php');
            $titles = [
                'dashboard' => 'Dashboard',
                'item-master-list' => 'Item Master List',
                'offices' => 'Offices',
                'report' => 'Report',
                'products' => 'Product Management',
                'cost' => 'Cost per Unit',
                'quantity-list' => 'Quantity List',
                'quantity-issued' => 'Quantity Issued'
            ];
            echo $titles[$page] ?? 'Dashboard';
            ?>
        </h1>
    </div>
    <div class="user-info">
        <div>
            <div class="user-name"><?php echo htmlspecialchars($user['name']); ?></div>
            <div style="font-size: 12px; color: #999;"><?php echo htmlspecialchars($user['email']); ?></div>
        </div>
        <span class="user-role"><?php echo htmlspecialchars($user['role']); ?></span>
        <form method="POST" action="logout.php" style="display: inline;">
            <button type="submit">Logout</button>
        </form>
    </div>
</nav>
