<?php declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\DB;
use PDO;
use function App\Core\require_auth;
use function App\Core\require_permission;
use function App\Core\verify_csrf_request;
use function App\Core\flash_set;
use function App\Core\redirect;
use function App\Core\base_url;

// Include TCPDF library
require_once __DIR__ . '/../libraries/tcpdf/tcpdf.php';

// Note: Using simplified Excel export without PhpSpreadsheet due to dependency issues

final class ImportExportController extends Controller
{
    public function index(): void
    {
        require_auth();
        require_permission('settings.view');
        
        $this->view('importexport/index', [
            'page_title' => \App\Core\t('importexport.import_export'),
        ]);
    }

    public function export(): void
    {
        require_auth();
        require_permission('settings.view');
        
        if (!verify_csrf_request()) {
            flash_set('error', 'Invalid session token.');
            redirect('/import');
        }

        $module = $_POST['module'] ?? '';
        $format = $_POST['format'] ?? '';
        $type = $_POST['type'] ?? 'bulk'; // bulk, module, custom

        if (empty($module) || empty($format)) {
            flash_set('error', 'Module and format are required.');
            redirect('/import');
        }

        try {
            switch ($format) {
                case 'csv':
                    $this->exportCSV($module, $type);
                    break;
                case 'xls':
                case 'xlsx':
                    $this->exportExcel($module, $type, $format);
                    break;
                case 'pdf':
                    $this->exportPDF($module, $type);
                    break;
                default:
                    flash_set('error', 'Unsupported export format.');
                    redirect('/import');
            }
        } catch (\Throwable $e) {
            flash_set('error', 'Export failed: ' . $e->getMessage());
            redirect('/import');
        }
    }

    private function exportCSV(string $module, string $type): void
    {
        $data = $this->getModuleData($module, $type);
        $filename = $this->generateFilename($module, 'csv');
        
        // Generate CSV content
        $csvContent = $this->generateCSVForExcel($data);
        
        // Save to file
        $exportDir = __DIR__ . '/../../export/';
        if (!is_dir($exportDir)) {
            mkdir($exportDir, 0755, true);
        }
        
        $filepath = $exportDir . $filename;
        file_put_contents($filepath, $csvContent);
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Output the CSV content
        echo $csvContent;
        exit;
    }

    private function exportExcel(string $module, string $type, string $format): void
    {
        $data = $this->getModuleData($module, $type);
        $filename = $this->generateFilename($module, $format);
        
        // Generate Excel-compatible content
        $excelContent = $this->generateExcelContent($module, $data, $format);
        
        // Save to file
        $exportDir = __DIR__ . '/../../export/';
        if (!is_dir($exportDir)) {
            mkdir($exportDir, 0755, true);
        }
        
        $filepath = $exportDir . $filename;
        file_put_contents($filepath, $excelContent);
        
        // Set appropriate headers based on format
        if ($format === 'xlsx') {
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        } else {
            header('Content-Type: application/vnd.ms-excel');
        }
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        header('Content-Length: ' . filesize($filepath));
        
        // Output the file
        readfile($filepath);
        exit;
    }

    private function exportPDF(string $module, string $type): void
    {
        $data = $this->getModuleData($module, $type);
        $filename = $this->generateFilename($module, 'pdf');
        
        // Create new TCPDF object
        $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        // Set document information
        $pdf->SetCreator('MI Spare Parts System');
        $pdf->SetAuthor('System Export');
        $pdf->SetTitle(ucfirst($module) . ' Export');
        $pdf->SetSubject('Data Export');
        
        // Set default header data
        $pdf->SetHeaderData('', 0, 'MI Spare Parts', ucfirst($module) . ' Export - ' . date('Y-m-d H:i:s'));
        
        // Set header and footer fonts
        $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
        $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
        
        // Set default monospaced font
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        
        // Set margins
        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
        
        // Set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
        
        // Set image scale factor
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        
        // Add a page
        $pdf->AddPage();
        
        // Set font
        $pdf->SetFont('helvetica', '', 10);
        
        // Generate table content
        if (!empty($data)) {
            $headers = array_keys($data[0]);
            
            // Create table
            $html = '<table border="1" cellpadding="4" cellspacing="0">';
            
            // Header row
            $html .= '<tr style="background-color:#f0f0f0; font-weight:bold;">';
            foreach ($headers as $header) {
                $html .= '<th>' . htmlspecialchars($header) . '</th>';
            }
            $html .= '</tr>';
            
            // Data rows
            foreach ($data as $row) {
                $html .= '<tr>';
                foreach ($row as $field) {
                    $html .= '<td>' . htmlspecialchars((string)$field) . '</td>';
                }
                $html .= '</tr>';
            }
            
            $html .= '</table>';
            
            // Write HTML content
            $pdf->writeHTML($html, true, false, true, false, '');
        } else {
            $pdf->writeHTML('<p>No data available for export.</p>', true, false, true, false, '');
        }
        
        // Save to file
        $exportDir = __DIR__ . '/../../export/';
        if (!is_dir($exportDir)) {
            mkdir($exportDir, 0755, true);
        }
        
        $filepath = $exportDir . $filename;
        $pdf->Output($filepath, 'F');
        
        // Set headers for download
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        header('Content-Length: ' . filesize($filepath));
        
        // Output the PDF file
        readfile($filepath);
        exit;
    }

    private function getModuleData(string $module, string $type): array
    {
        $pdo = DB::conn();
        
        switch ($module) {
            case 'products':
                return $this->getProductsData($pdo, $type);
            case 'customers':
                return $this->getCustomersData($pdo, $type);
            case 'suppliers':
                return $this->getSuppliersData($pdo, $type);
            case 'invoices':
                return $this->getInvoicesData($pdo, $type);
            case 'quotes':
                return $this->getQuotesData($pdo, $type);
            case 'orders':
                return $this->getOrdersData($pdo, $type);
            case 'purchase_orders':
                return $this->getPurchaseOrdersData($pdo, $type);
            case 'purchase_invoices':
                return $this->getPurchaseInvoicesData($pdo, $type);
            case 'warehouses':
                return $this->getWarehousesData($pdo, $type);
            case 'categories':
                return $this->getCategoriesData($pdo, $type);
            case 'makes':
                return $this->getMakesData($pdo, $type);
            case 'models':
                return $this->getModelsData($pdo, $type);
            case 'users':
                return $this->getUsersData($pdo, $type);
            case 'all':
                return $this->getAllData($pdo, $type);
            default:
                throw new \InvalidArgumentException('Unknown module: ' . $module);
        }
    }

    private function getProductsData(PDO $pdo, string $type): array
    {
        // Check which tables exist to build appropriate query
        $tables = $this->getExistingTables($pdo);
        
        $selectFields = [
            'p.id',
            'p.code',
            'p.name',
            'p.cost',
            'p.price',
            'p.created_at',
            'p.updated_at'
        ];
        
        $joins = [];
        
        // Add category join if table exists
        if (in_array('categories', $tables)) {
            $selectFields[] = 'p.category_id';
            $selectFields[] = 'c.name as category_name';
            $joins[] = 'LEFT JOIN categories c ON c.id = p.category_id';
        }
        
        // Add make join if table exists
        if (in_array('makes', $tables)) {
            $selectFields[] = 'p.make_id';
            $selectFields[] = 'm.name as make_name';
            $joins[] = 'LEFT JOIN makes m ON m.id = p.make_id';
        }
        
        // Add model join if table exists
        if (in_array('models', $tables)) {
            $selectFields[] = 'p.model_id';
            $selectFields[] = 'mo.name as model_name';
            $joins[] = 'LEFT JOIN models mo ON mo.id = p.model_id';
        }
        
        $sql = "SELECT " . implode(', ', $selectFields) . "
                FROM products p
                " . implode(' ', $joins) . "
                ORDER BY p.name";
        
        return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function getCustomersData(PDO $pdo, string $type): array
    {
        $sql = "SELECT 
                    id,
                    name,
                    email,
                    phone,
                    address,
                    city,
                    country,
                    credit_limit,
                    status,
                    created_at,
                    updated_at
                FROM customers
                ORDER BY name";
        
        return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function getSuppliersData(PDO $pdo, string $type): array
    {
        $sql = "SELECT 
                    id,
                    name,
                    email,
                    phone,
                    address,
                    city,
                    country,
                    contact_person,
                    payment_terms,
                    status,
                    created_at,
                    updated_at
                FROM suppliers
                ORDER BY name";
        
        return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function getInvoicesData(PDO $pdo, string $type): array
    {
        $sql = "SELECT 
                    i.id,
                    i.inv_no,
                    i.customer_id,
                    c.name as customer_name,
                    i.status,
                    i.subtotal,
                    i.tax_amount,
                    i.total,
                    i.paid_amount,
                    i.balance,
                    i.due_date,
                    i.created_at,
                    i.updated_at
                FROM invoices i
                LEFT JOIN customers c ON c.id = i.customer_id
                ORDER BY i.created_at DESC";
        
        return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function getQuotesData(PDO $pdo, string $type): array
    {
        $sql = "SELECT 
                    q.id,
                    q.quote_no,
                    q.customer_id,
                    c.name as customer_name,
                    q.status,
                    q.subtotal,
                    q.tax_amount,
                    q.total,
                    q.valid_until,
                    q.created_at,
                    q.updated_at
                FROM quotes q
                LEFT JOIN customers c ON c.id = q.customer_id
                ORDER BY q.created_at DESC";
        
        return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function getOrdersData(PDO $pdo, string $type): array
    {
        $sql = "SELECT 
                    so.id,
                    so.so_no,
                    so.customer_id,
                    c.name as customer_name,
                    so.status,
                    so.subtotal,
                    so.tax_amount,
                    so.total,
                    so.delivery_date,
                    so.created_at,
                    so.updated_at
                FROM sales_orders so
                LEFT JOIN customers c ON c.id = so.customer_id
                ORDER BY so.created_at DESC";
        
        return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function getPurchaseOrdersData(PDO $pdo, string $type): array
    {
        $sql = "SELECT 
                    po.id,
                    po.po_no,
                    po.supplier_id,
                    s.name as supplier_name,
                    po.status,
                    po.subtotal,
                    po.tax_amount,
                    po.total,
                    po.expected_date,
                    po.created_at,
                    po.updated_at
                FROM purchase_orders po
                LEFT JOIN suppliers s ON s.id = po.supplier_id
                ORDER BY po.created_at DESC";
        
        return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function getPurchaseInvoicesData(PDO $pdo, string $type): array
    {
        $sql = "SELECT 
                    pi.id,
                    pi.pi_no,
                    pi.supplier_id,
                    s.name as supplier_name,
                    pi.status,
                    pi.subtotal,
                    pi.tax_amount,
                    pi.total,
                    pi.paid_amount,
                    pi.balance,
                    pi.due_date,
                    pi.created_at,
                    pi.updated_at
                FROM purchase_invoices pi
                LEFT JOIN suppliers s ON s.id = pi.supplier_id
                ORDER BY pi.created_at DESC";
        
        return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function getWarehousesData(PDO $pdo, string $type): array
    {
        $sql = "SELECT 
                    id,
                    name,
                    address,
                    city,
                    country,
                    manager_name,
                    phone,
                    email,
                    status,
                    created_at,
                    updated_at
                FROM warehouses
                ORDER BY name";
        
        return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function getCategoriesData(PDO $pdo, string $type): array
    {
        $tables = $this->getExistingTables($pdo);
        
        if (!in_array('categories', $tables)) {
            return []; // Return empty array if categories table doesn't exist
        }
        
        $sql = "SELECT 
                    id,
                    name,
                    slug,
                    parent_id,
                    created_at,
                    updated_at
                FROM categories
                ORDER BY name";
        
        return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function getMakesData(PDO $pdo, string $type): array
    {
        $tables = $this->getExistingTables($pdo);
        
        if (!in_array('makes', $tables)) {
            return []; // Return empty array if makes table doesn't exist
        }
        
        $sql = "SELECT 
                    id,
                    name,
                    slug,
                    created_at,
                    updated_at
                FROM makes
                ORDER BY name";
        
        return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function getModelsData(PDO $pdo, string $type): array
    {
        $tables = $this->getExistingTables($pdo);
        
        if (!in_array('models', $tables)) {
            return []; // Return empty array if models table doesn't exist
        }
        
        $selectFields = [
            'm.id',
            'm.name',
            'm.description',
            'm.status',
            'm.created_at',
            'm.updated_at'
        ];
        
        $joins = [];
        
        // Add make join if table exists
        if (in_array('makes', $tables)) {
            $selectFields[] = 'm.make_id';
            $selectFields[] = 'ma.name as make_name';
            $joins[] = 'LEFT JOIN makes ma ON ma.id = m.make_id';
        }
        
        $sql = "SELECT " . implode(', ', $selectFields) . "
                FROM models m
                " . implode(' ', $joins) . "
                ORDER BY m.name";
        
        return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function getUsersData(PDO $pdo, string $type): array
    {
        $sql = "SELECT 
                    id,
                    email,
                    status,
                    last_login,
                    created_at,
                    updated_at
                FROM users
                ORDER BY email";
        
        return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function getAllData(PDO $pdo, string $type): array
    {
        // For bulk export, we'll export a summary of all modules
        $modules = [
            'products' => $this->getProductsData($pdo, $type),
            'customers' => $this->getCustomersData($pdo, $type),
            'suppliers' => $this->getSuppliersData($pdo, $type),
            'invoices' => $this->getInvoicesData($pdo, $type),
            'quotes' => $this->getQuotesData($pdo, $type),
            'orders' => $this->getOrdersData($pdo, $type),
            'warehouses' => $this->getWarehousesData($pdo, $type),
            'categories' => $this->getCategoriesData($pdo, $type),
            'makes' => $this->getMakesData($pdo, $type),
            'models' => $this->getModelsData($pdo, $type),
            'users' => $this->getUsersData($pdo, $type)
        ];

        $summary = [];
        foreach ($modules as $module => $data) {
            $summary[] = [
                'Module' => ucfirst(str_replace('_', ' ', $module)),
                'Records' => count($data),
                'Last_Updated' => !empty($data) ? max(array_column($data, 'updated_at')) : 'N/A'
            ];
        }

        return $summary;
    }

    private function generateFilename(string $module, string $format): string
    {
        $timestamp = date('Y-m-d_H-i-s');
        $module = str_replace('_', '-', $module);
        return "{$module}_export_{$timestamp}.{$format}";
    }

    private function getExistingTables(PDO $pdo): array
    {
        try {
            $stmt = $pdo->query("SHOW TABLES");
            $tables = [];
            while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
                $tables[] = $row[0];
            }
            return $tables;
        } catch (\Throwable $e) {
            // If SHOW TABLES fails, return empty array
            return [];
        }
    }

    private function generateHTMLForPDF(string $module, array $data): string
    {
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>' . htmlspecialchars(ucfirst($module)) . ' Export</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 20px; 
            font-size: 12px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .header h1 { 
            color: #333; 
            margin: 0;
            font-size: 24px;
        }
        .header p { 
            margin: 5px 0;
            color: #666;
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 20px;
            font-size: 10px;
        }
        th, td { 
            border: 1px solid #ddd; 
            padding: 6px; 
            text-align: left; 
        }
        th { 
            background-color: #f2f2f2; 
            font-weight: bold;
            font-size: 11px;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>' . htmlspecialchars(ucfirst(str_replace('_', ' ', $module))) . ' Export</h1>
        <p>Generated on: ' . date('Y-m-d H:i:s') . '</p>
        <p>Total Records: ' . count($data) . '</p>
    </div>';

        if (!empty($data)) {
            $html .= '<table>
                <thead>
                    <tr>';
            foreach (array_keys($data[0]) as $header) {
                $html .= '<th>' . htmlspecialchars(str_replace('_', ' ', ucfirst($header))) . '</th>';
            }
            $html .= '</tr>
                </thead>
                <tbody>';
            
            foreach ($data as $row) {
                $html .= '<tr>';
                foreach ($row as $cell) {
                    $html .= '<td>' . htmlspecialchars((string)$cell) . '</td>';
                }
                $html .= '</tr>';
            }
            
            $html .= '</tbody>
            </table>';
        } else {
            $html .= '<p>No data available for export.</p>';
        }

        $html .= '<div class="footer">
            <p>This document was generated by MI Spare Parts Management System</p>
        </div>
</body>
</html>';

        return $html;
    }

    private function generateCSVForExcel(array $data): string
    {
        if (empty($data)) {
            return '';
        }

        $output = '';
        
        // Add BOM for UTF-8 to ensure proper encoding in Excel
        $output .= "\xEF\xBB\xBF";
        
        // Write headers
        $headers = array_keys($data[0]);
        $output .= $this->arrayToCSV($headers) . "\n";
        
        // Write data rows
        foreach ($data as $row) {
            $output .= $this->arrayToCSV($row) . "\n";
        }
        
        return $output;
    }

    private function arrayToCSV(array $row): string
    {
        $csv = '';
        $first = true;
        
        foreach ($row as $field) {
            if (!$first) {
                $csv .= ',';
            }
            
            // Convert field to string to handle integers, floats, nulls, etc.
            $fieldStr = (string)$field;
            
            // Escape field if it contains comma, quote, or newline
            if (strpos($fieldStr, ',') !== false || strpos($fieldStr, '"') !== false || strpos($fieldStr, "\n") !== false) {
                $csv .= '"' . str_replace('"', '""', $fieldStr) . '"';
            } else {
                $csv .= $fieldStr;
            }
            
            $first = false;
        }
        
        return $csv;
    }

    private function generateExcelContent(string $module, array $data, string $format): string
    {
        if (empty($data)) {
            // Return a simple CSV format for empty data
            return "No data available for export\n";
        }

        if ($format === 'xlsx') {
            // For XLSX, create a simple HTML table that Excel can open
            return $this->generateExcelHTML($module, $data);
        } else {
            // For XLS, use the XML format but with better structure
            return $this->generateExcelXML($module, $data);
        }
    }

    private function generateExcelHTML(string $module, array $data): string
    {
        $headers = array_keys($data[0]);
        
        $html = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">' . "\n";
        $html .= '<head>' . "\n";
        $html .= '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">' . "\n";
        $html .= '<meta name="ProgId" content="Excel.Sheet">' . "\n";
        $html .= '<meta name="Generator" content="MI Spare Parts System">' . "\n";
        $html .= '<!--[if gte mso 9]><xml>' . "\n";
        $html .= '<x:ExcelWorkbook>' . "\n";
        $html .= '<x:ExcelWorksheets>' . "\n";
        $html .= '<x:ExcelWorksheet>' . "\n";
        $html .= '<x:Name>' . htmlspecialchars($module) . '</x:Name>' . "\n";
        $html .= '<x:WorksheetOptions>' . "\n";
        $html .= '<x:DefaultRowHeight>285</x:DefaultRowHeight>' . "\n";
        $html .= '</x:WorksheetOptions>' . "\n";
        $html .= '</x:ExcelWorksheet>' . "\n";
        $html .= '</x:ExcelWorksheets>' . "\n";
        $html .= '</x:ExcelWorkbook>' . "\n";
        $html .= '</xml><![endif]-->' . "\n";
        $html .= '</head>' . "\n";
        $html .= '<body>' . "\n";
        $html .= '<table border="1" cellpadding="0" cellspacing="0">' . "\n";
        
        // Header row
        $html .= '<tr style="background-color:#E0E0E0; font-weight:bold;">' . "\n";
        foreach ($headers as $header) {
            $html .= '<td>' . htmlspecialchars($header) . '</td>' . "\n";
        }
        $html .= '</tr>' . "\n";
        
        // Data rows
        foreach ($data as $row) {
            $html .= '<tr>' . "\n";
            foreach ($row as $field) {
                $fieldStr = (string)$field;
                $html .= '<td>' . htmlspecialchars($fieldStr) . '</td>' . "\n";
            }
            $html .= '</tr>' . "\n";
        }
        
        $html .= '</table>' . "\n";
        $html .= '</body>' . "\n";
        $html .= '</html>';
        
        return $html;
    }

    private function generateExcelXML(string $module, array $data): string
    {
        $headers = array_keys($data[0]);
        
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"' . "\n";
        $xml .= ' xmlns:o="urn:schemas-microsoft-com:office:office"' . "\n";
        $xml .= ' xmlns:x="urn:schemas-microsoft-com:office:excel"' . "\n";
        $xml .= ' xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"' . "\n";
        $xml .= ' xmlns:html="http://www.w3.org/TR/REC-html40">' . "\n";
        $xml .= '<DocumentProperties xmlns="urn:schemas-microsoft-com:office:office">' . "\n";
        $xml .= '<Title>' . htmlspecialchars(ucfirst($module) . ' Export') . '</Title>' . "\n";
        $xml .= '<Author>MI Spare Parts System</Author>' . "\n";
        $xml .= '<Created>' . date('c') . '</Created>' . "\n";
        $xml .= '</DocumentProperties>' . "\n";
        $xml .= '<Styles>' . "\n";
        $xml .= '<Style ss:ID="Header">' . "\n";
        $xml .= '<Font ss:Bold="1"/>' . "\n";
        $xml .= '<Interior ss:Color="#E0E0E0" ss:Pattern="Solid"/>' . "\n";
        $xml .= '</Style>' . "\n";
        $xml .= '</Styles>' . "\n";
        $xml .= '<Worksheet ss:Name="' . htmlspecialchars($module) . '">' . "\n";
        $xml .= '<Table>' . "\n";
        
        // Header row
        $xml .= '<Row>' . "\n";
        foreach ($headers as $header) {
            $xml .= '<Cell ss:StyleID="Header"><Data ss:Type="String">' . htmlspecialchars($header) . '</Data></Cell>' . "\n";
        }
        $xml .= '</Row>' . "\n";
        
        // Data rows
        foreach ($data as $row) {
            $xml .= '<Row>' . "\n";
            foreach ($row as $field) {
                $fieldStr = (string)$field;
                // Determine data type
                if (is_numeric($fieldStr) && !empty($fieldStr)) {
                    $type = 'Number';
                } else {
                    $type = 'String';
                }
                $xml .= '<Cell><Data ss:Type="' . $type . '">' . htmlspecialchars($fieldStr) . '</Data></Cell>' . "\n";
            }
            $xml .= '</Row>' . "\n";
        }
        
        $xml .= '</Table>' . "\n";
        $xml .= '</Worksheet>' . "\n";
        $xml .= '</Workbook>';
        
        return $xml;
    }
}
