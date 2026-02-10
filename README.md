# 🏛️ Mabini Inventory Management System

A comprehensive inventory management system for the Municipality of Mabini, built with PHP and designed for efficient inventory tracking and management.

## ✨ Features

- 🔐 **Secure Login System** - Admin authentication with session management
- 📊 **Dashboard Overview** - Real-time statistics and insights
- 📦 **Product Management** - Add, edit, and delete products
- 💰 **Cost Tracking** - Monitor unit costs for all products
- 📋 **Quantity Management** - Track available inventory quantities
- 📤 **Issuance Tracking** - Record and monitor issued items
- 🎨 **Modern UI** - Clean design with green and blue theme
- 📱 **Responsive Design** - Works on desktop and mobile devices

## 🚀 Getting Started

### Prerequisites

- **XAMPP** (or any PHP server with PHP 7.4+)
- **Web Browser** (Chrome, Firefox, Edge, etc.)

### Installation

1. **Clone the repository**
   ```bash
   git clone <your-repository-url>
   ```

2. **Move to XAMPP htdocs**
   ```bash
   # Copy the folder to your XAMPP htdocs directory
   # Typically: C:\xampp\htdocs\
   ```

3. **Start XAMPP**
   - Start Apache from XAMPP Control Panel

4. **Access the application**
   - Open browser and navigate to: `http://localhost/InventorySystemMabini`

### Default Login Credentials

- **Email:** `admin@mabini.com`
- **Password:** `password`

> ⚠️ **Important:** Change the default password after first login!

## 📁 Project Structure

```
InventorySystemMabini/
├── includes/
│   ├── sidebar.php          # Shared sidebar navigation
│   └── navbar.php           # Shared navigation bar
├── index.php                # Login page
├── login_process.php        # Authentication handler
├── dashboard.php            # Main dashboard
├── products.php             # Product management
├── cost.php                 # Cost per unit management
├── quantity-list.php        # Quantity tracking
├── quantity-issued.php      # Issuance tracking
├── logout.php               # Logout handler
├── style.css                # Main stylesheet
├── script.js                # JavaScript functions
└── Screenshot 2026-02-05 100742.png  # Mabini logo
```

## 🛠️ Technologies Used

- **Backend:** PHP 8.2+ (session-based)
- **Frontend:** HTML5, CSS3, JavaScript
- **Fonts:** Google Fonts (Poppins)
- **Design:** Gradient theme (Green #4CAF50 & Blue #2196F3)

## 👥 Team Collaboration

This repository is set up for team collaboration:

1. **Clone the repository** to your local machine
2. **Create a branch** for your features: `git checkout -b feature-name`
3. **Make changes** and commit: `git commit -am "Description of changes"`
4. **Push to GitHub**: `git push origin feature-name`
5. **Create a Pull Request** for review

## 📝 Usage Guide

### Managing Products
1. Navigate to **Product** section from sidebar
2. Click **+ Add Product** button
3. Fill in product details (name, description, category)
4. Click **Add Product** to save

### Tracking Costs
1. Go to **Cost per Unit** section
2. Add product name and unit cost in PHP Pesos (₱)
3. System automatically tracks and updates costs

### Managing Quantities
1. Access **Quantity List** section
2. Enter product name, quantity, and unit (pcs, kg, liters)
3. Monitor available inventory levels

### Issuing Items
1. Navigate to **Quantity Issued** section
2. Select product, enter quantity and recipient
3. System records date and tracks all issuances

## 🔒 Security Notes

- All user inputs are sanitized with `htmlspecialchars()`
- Session-based authentication
- CSRF protection recommended for production
- Change default credentials immediately
- Use HTTPS in production environment

## 🐛 Known Limitations

- Currently uses PHP sessions (no database)
- Data resets when session expires
- Single admin user only
- No email notifications
- No backup/export functionality

## 🔮 Future Enhancements

- [ ] MySQL database integration
- [ ] Multi-user support with roles
- [ ] PDF report generation
- [ ] Email notifications
- [ ] Barcode scanning support
- [ ] Data export (CSV, Excel)
- [ ] Audit trail logging
- [ ] Dashboard charts and graphs

## 📄 License

This project is developed for the Municipality of Mabini.

## 👨‍💻 Contributing

Contributions are welcome! Please follow these steps:

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## 📞 Support

For issues or questions, please create an issue in the GitHub repository.

---

**Developed with ❤️ for Mabini Municipality**
