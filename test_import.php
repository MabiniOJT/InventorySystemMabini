<?php
session_start();

// Initialize session arrays
if (!isset($_SESSION['products'])) $_SESSION['products'] = [];

require_once 'vendor/autoload.php';

$file = 'products_import_template.xlsx';

if (file_exists($file)) {
    try {
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();
        
        $imported = 0;
        // Skip header row (first row)
        for ($i = 1; $i < count($rows); $i++) {
            $row = $rows[$i];
            
            // Skip empty rows
            if (empty($row[0]) && empty($row[1]) && empty($row[2])) continue;
            
            // Assuming columns: Name, Price, Date Issued
            $name = $row[0] ?? '';
            $price = $row[1] ?? 0;
            $dateIssued = $row[2] ?? date('Y-m-d');
            
            // Convert Excel date to PHP date if numeric
            if (is_numeric($dateIssued)) {
                $dateIssued = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($dateIssued)->format('Y-m-d');
            }
            
            if (!empty($name)) {
                $_SESSION['products'][] = [
                    'id' => count($_SESSION['products']) + 1,
                    'name' => $name,
                    'description' => '',
                    'category' => '',
                    'price' => $price,
                    'date_issued' => $dateIssued,
                    'created_at' => date('Y-m-d H:i:s')
                ];
                $imported++;
                echo "✓ Imported: $name - \$$price - $dateIssued\n";
            }
        }
        
        echo "\n✅ Successfully imported $imported products!\n";
        echo "Visit products.php to see the imported products.\n";
        
    } catch (Exception $e) {
        echo '❌ Error reading Excel file: ' . $e->getMessage() . "\n";
    }
} else {
    echo "❌ Template file not found: $file\n";
}
?>
