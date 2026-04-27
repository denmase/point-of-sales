<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Picqer\Barcode\BarcodeGeneratorPNG;

class MobileDocumentController extends Controller
{
    public function show(Request $request, string $invoice, string $variant)
    {
        abort_unless($request->user()->can('transactions-access'), 403, 'Anda tidak memiliki izin untuk mengakses dokumen transaksi.');

        $transaction = Transaction::query()
            ->with(['details.product', 'cashier:id,name', 'customer', 'receivable', 'bankAccount'])
            ->where('invoice', $invoice)
            ->firstOrFail();

        if (! $request->user()->isSuperAdmin() && $transaction->cashier_id !== $request->user()->id) {
            abort(403, 'Anda tidak memiliki akses ke transaksi ini.');
        }

        [$view, $title, $filename] = match ($variant) {
            'invoice' => ['pdf.invoice', 'Invoice', "invoice-{$transaction->invoice}.pdf"],
            'receipt-80' => ['pdf.receipt_80', 'Struk 80mm', "receipt-{$transaction->invoice}-80mm.pdf"],
            'receipt-58' => ['pdf.receipt_58', 'Struk 58mm', "receipt-{$transaction->invoice}-58mm.pdf"],
            'shipping' => ['pdf.shipping_label', 'Resi', "shipping-{$transaction->invoice}.pdf"],
            default => abort(404, 'Dokumen tidak ditemukan.'),
        };

        return response()->json([
            'document' => [
                'variant' => $variant,
                'title' => $title,
                'filename' => $filename,
                'html' => view($view, [
                    'transaction' => $transaction,
                    'store' => $this->storeProfile(),
                    'barcode' => $this->barcode($transaction->invoice),
                ])->render(),
            ],
        ]);
    }

    private function storeProfile(): array
    {
        $logo = Setting::get('store_logo');
        if ($logo && ! str_starts_with($logo, 'http') && ! str_starts_with($logo, '/storage')) {
            $logo = asset('storage/' . ltrim($logo, '/'));
        }

        $logoData = null;
        if ($logo) {
            $localPath = null;
            if (str_starts_with($logo, asset('storage'))) {
                $localPath = public_path(str_replace(asset(''), '', $logo));
            } elseif (str_starts_with($logo, '/storage')) {
                $localPath = public_path($logo);
            }

            if ($localPath && file_exists($localPath)) {
                $logoData = 'data:image/png;base64,' . base64_encode(file_get_contents($localPath));
            }
        }

        return [
            'name' => Setting::get('store_name', 'Toko Anda'),
            'logo' => $logo,
            'logo_data' => $logoData,
            'address' => Setting::get('store_address', ''),
            'phone' => Setting::get('store_phone', ''),
            'email' => Setting::get('store_email', ''),
            'website' => Setting::get('store_website', ''),
        ];
    }

    private function barcode(string $code): string
    {
        $generator = new BarcodeGeneratorPNG();
        $data = $generator->getBarcode($code, $generator::TYPE_CODE_128);

        return 'data:image/png;base64,' . base64_encode($data);
    }
}
