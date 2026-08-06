<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\Tenant;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class PurchaseOrderPdfController extends Controller
{
    public function show(PurchaseOrder $purchaseOrder): Response
    {
        $purchaseOrder->load(['items.product', 'charges', 'supplier', 'location']);

        $pdf = Pdf::loadView('pdf.purchase-order', [
            'purchaseOrder' => $purchaseOrder,
            'tenant' => Tenant::findOrFail(tenant('id')),
            'currency' => tenant('currency')?->value,
        ]);

        return $pdf->stream("{$purchaseOrder->po_number}.pdf");
    }
}
