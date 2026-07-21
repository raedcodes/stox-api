<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Tenant;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class SaleInvoiceController extends Controller
{
    public function show(Sale $sale): Response
    {
        $sale->load(['items.product', 'customer']);

        $pdf = Pdf::loadView('pdf.sale-invoice', [
            'sale' => $sale,
            'tenant' => Tenant::findOrFail(tenant('id')),
            'currency' => tenant('currency')?->value,
        ]);

        return $pdf->stream("{$sale->sale_number}.pdf");
    }
}
