<?php
/**
 * This script generates a sample Excel template for product import
 * Run this file once to create a template file, then download it and use it as a reference
 */

require_once 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Set headers
$sheet->setCellValue('A1', 'Name');
$sheet->setCellValue('B1', 'Price');
$sheet->setCellValue('C1', 'Date Issued');

// Style headers
$headerStyle = [
    'font' => [
        'bold' => true,
        'color' => ['rgb' => 'FFFFFF'],
    ],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => '4CAF50'],
    ],
];
$sheet->getStyle('A1:C1')->applyFromArray($headerStyle);

// Add sample data
$sheet->setCellValue('A2', 'Laptop Dell XPS 15');
$sheet->setCellValue('B2', 1299.99);
$sheet->setCellValue('C2', '2026-02-10');

$sheet->setCellValue('A3', 'Wireless Mouse Logitech');
$sheet->setCellValue('B3', 29.99);
$sheet->setCellValue('C3', '2026-02-09');

$sheet->setCellValue('A4', 'USB-C Hub Multiport');
$sheet->setCellValue('B4', 49.99);
$sheet->setCellValue('C4', '2026-02-08');

// Set column widths
$sheet->getColumnDimension('A')->setWidth(30);
$sheet->getColumnDimension('B')->setWidth(15);
$sheet->getColumnDimension('C')->setWidth(15);

// Save file
$writer = new Xlsx($spreadsheet);
$filename = 'products_import_template.xlsx';
$writer->save($filename);

echo "Sample template created successfully: $filename\n";
echo "You can now download this file and use it as a template for importing products.";
?>
