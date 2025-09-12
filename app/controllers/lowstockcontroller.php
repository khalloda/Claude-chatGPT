<?php declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Product;
use App\Core\DB;
use PDO;

use function App\Core\require_auth;

final class LowstockController extends Controller
{
    public function index(): void
    {
        require_auth();

        $threshold   = isset($_GET['threshold']) && $_GET['threshold'] !== '' ? (int)$_GET['threshold'] : 5;
        $warehouseId = isset($_GET['warehouse_id']) && $_GET['warehouse_id'] !== '' ? (int)$_GET['warehouse_id'] : null;

        $rows = Product::getLowStock($threshold, $warehouseId);

        // Warehouses for filter
        $warehouses = DB::conn()->query('SELECT id, name FROM warehouses ORDER BY name')->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $this->view('products/lowstock', [
            'rows'        => $rows,
            'threshold'   => $threshold,
            'warehouse_id'=> $warehouseId,
            'warehouses'  => $warehouses,
        ]);
    }
}

