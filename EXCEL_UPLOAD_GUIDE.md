# Excel Upload Instructions for Products

## How to Upload Products via Excel File

### Step 1: Prepare Your Excel File

Your Excel file should have **3 columns** in this exact order:

1. **Name** - Product name (text)
2. **Price** - Product price (number, can include decimals)
3. **Date Issued** - Date the product was issued (format: YYYY-MM-DD or Excel date)

### Step 2: Excel File Format Example

| Name                    | Price   | Date Issued |
|-------------------------|---------|-------------|
| Laptop Dell XPS 15      | 1299.99 | 2026-02-10  |
| Wireless Mouse Logitech | 29.99   | 2026-02-09  |
| USB-C Hub Multiport     | 49.99   | 2026-02-08  |

**Important Notes:**
- The first row should contain headers (Name, Price, Date Issued)
- Empty rows will be skipped automatically
- Dates can be in YYYY-MM-DD format or Excel date format
- Prices should be numeric values (decimals allowed)
- Description and Category fields will be empty for imported products (you can edit them manually later)

### Step 3: Using the Upload Feature

1. Go to the **Products** page
2. Click the **"📤 Upload Excel"** button
3. Select your Excel file (.xlsx, .xls, or .csv)
4. Click **"Upload & Import"**
5. The system will process and import all products from your file

### Sample Template

A sample template file `products_import_template.xlsx` has been created in your project folder. You can:
- Open it to see the correct format
- Use it as a starting point for your own data
- Copy and paste your product data into it

### Supported File Formats

- **.xlsx** - Excel 2007 and later
- **.xls** - Excel 97-2003
- **.csv** - Comma-separated values

### Troubleshooting

**Error: Invalid file format**
- Make sure your file has .xlsx, .xls, or .csv extension

**Error: No products imported**
- Check that your first row contains headers
- Verify that product names are not empty
- Ensure the file is not corrupted

**Some products not imported**
- Empty rows are automatically skipped
- Rows with empty product names are skipped
- Check the success message to see how many products were imported

### Manual Addition

If you prefer to add products one by one, you can still use the **"+ Add Product"** button to manually enter:
- Product Name
- Description
- Category
- Price
- Date Issued

---

For questions or issues, please contact your system administrator.
