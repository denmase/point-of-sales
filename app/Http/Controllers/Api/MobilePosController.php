<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\PaymentGatewayException;
use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\Cart;
use App\Models\Category;
use App\Models\Customer;
use App\Models\PaymentSetting;
use App\Models\Product;
use App\Models\Receivable;
use App\Models\Transaction;
use App\Services\Payments\PaymentGatewayManager;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravolt\Indonesia\Models\City;
use Laravolt\Indonesia\Models\District;
use Laravolt\Indonesia\Models\Province;
use Laravolt\Indonesia\Models\Village;

class MobilePosController extends Controller
{
    public function bootstrap(Request $request)
    {
        $this->authorizeTransactions($request);

        $paymentSetting = PaymentSetting::first();

        return response()->json([
            'user'                => [
                'id'    => $request->user()->id,
                'name'  => $request->user()->name,
                'email' => $request->user()->email,
            ],
            'categories'          => Category::query()
                ->select('id', 'name', 'description', 'image')
                ->orderBy('name')
                ->get(),
            'customers'           => Customer::query()
                ->select(
                    'id',
                    'name',
                    'no_telp',
                    'address',
                    'province_name',
                    'regency_name',
                    'district_name',
                    'village_name'
                )
                ->latest()
                ->limit(50)
                ->get(),
            'payment_gateways'    => $paymentSetting?->enabledGateways() ?? [],
            'default_gateway'     => $paymentSetting?->default_gateway ?? 'cash',
            'bank_accounts'       => BankAccount::active()->ordered()->get(),
            'cart'                => $this->cartPayload($request->user()->id),
            'held_carts'          => $this->heldCartPayload($request->user()->id),
            'dashboard_highlights' => [
                'products'     => Product::count(),
                'customers'    => Customer::count(),
                'transactions' => Transaction::count(),
            ],
        ]);
    }

    public function products(Request $request)
    {
        $this->authorizeTransactions($request);

        $products = Product::query()
            ->with('category:id,name')
            ->when($request->filled('search'), function (Builder $query) use ($request) {
                $search = $request->string('search')->trim();

                $query->where(function (Builder $builder) use ($search) {
                    $builder->where('title', 'like', "%{$search}%")
                        ->orWhere('barcode', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('category_id'), function (Builder $query) use ($request) {
                $query->where('category_id', $request->integer('category_id'));
            })
            ->where('stock', '>', 0)
            ->orderBy('title')
            ->paginate($request->integer('per_page', 20))
            ->withQueryString();

        return response()->json($products);
    }

    public function customers(Request $request)
    {
        $this->authorizeTransactions($request);

        $customers = Customer::query()
            ->when($request->filled('search'), function (Builder $query) use ($request) {
                $search = $request->string('search')->trim();

                $query->where(function (Builder $builder) use ($search) {
                    $builder->where('name', 'like', "%{$search}%")
                        ->orWhere('no_telp', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate($request->integer('per_page', 20))
            ->withQueryString();

        return response()->json($customers);
    }

    public function storeCustomer(Request $request)
    {
        $this->authorizeTransactions($request);
        abort_unless($request->user()->can('customers-create'), 403, 'Anda tidak memiliki izin untuk menambah pelanggan.');

        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'no_telp'     => ['required', 'string', 'max:50', 'unique:customers,no_telp'],
            'address'     => ['required', 'string'],
            'province_id' => ['nullable', 'string'],
            'regency_id'  => ['nullable', 'string'],
            'district_id' => ['nullable', 'string'],
            'village_id'  => ['nullable', 'string'],
        ]);

        $province = filled($validated['province_id'] ?? null) ? Province::where('code', $validated['province_id'])->first() : null;
        $regency = filled($validated['regency_id'] ?? null) ? City::where('code', $validated['regency_id'])->first() : null;
        $district = filled($validated['district_id'] ?? null) ? District::where('code', $validated['district_id'])->first() : null;
        $village = filled($validated['village_id'] ?? null) ? Village::where('code', $validated['village_id'])->first() : null;

        $customer = Customer::create([
            'name'          => $validated['name'],
            'no_telp'       => $validated['no_telp'],
            'address'       => $validated['address'],
            'province_id'   => $validated['province_id'] ?? null,
            'province_name' => $province?->name,
            'regency_id'    => $validated['regency_id'] ?? null,
            'regency_name'  => $regency?->name,
            'district_id'   => $validated['district_id'] ?? null,
            'district_name' => $district?->name,
            'village_id'    => $validated['village_id'] ?? null,
            'village_name'  => $village?->name,
        ]);

        return response()->json([
            'message'  => 'Pelanggan berhasil ditambahkan.',
            'customer' => $customer,
        ], 201);
    }

    public function customerHistory(Request $request, Customer $customer)
    {
        $this->authorizeTransactions($request);

        $stats = Transaction::where('customer_id', $customer->id)
            ->selectRaw('COUNT(*) as total_transactions, SUM(grand_total) as total_spent, MAX(created_at) as last_visit')
            ->first();

        $recentTransactions = Transaction::where('customer_id', $customer->id)
            ->select('id', 'invoice', 'grand_total', 'payment_method', 'payment_status', 'created_at')
            ->orderByDesc('id')
            ->limit(5)
            ->get()
            ->map(fn (Transaction $transaction) => [
                'id'             => $transaction->id,
                'invoice'        => $transaction->invoice,
                'grand_total'    => (int) $transaction->grand_total,
                'payment_method' => $transaction->payment_method,
                'payment_status' => $transaction->payment_status,
                'created_at'     => $this->rawDate($transaction->getRawOriginal('created_at')),
            ]);

        return response()->json([
            'customer'            => $customer,
            'stats'               => [
                'total_transactions' => (int) ($stats->total_transactions ?? 0),
                'total_spent'        => (int) ($stats->total_spent ?? 0),
                'last_visit'         => $this->rawDate($stats->last_visit),
            ],
            'recent_transactions' => $recentTransactions,
        ]);
    }

    public function cart(Request $request)
    {
        $this->authorizeTransactions($request);

        return response()->json($this->cartPayload($request->user()->id));
    }

    public function addCartItem(Request $request)
    {
        $this->authorizeTransactions($request);

        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'qty'        => ['required', 'integer', 'min:1'],
        ]);

        $product = Product::findOrFail($validated['product_id']);

        if ($product->stock < $validated['qty']) {
            return response()->json([
                'message' => 'Stok tidak mencukupi.',
            ], 422);
        }

        $cart = Cart::query()
            ->where('cashier_id', $request->user()->id)
            ->where('product_id', $product->id)
            ->whereNull('hold_id')
            ->first();

        if ($cart) {
            $newQty = $cart->qty + $validated['qty'];

            if ($product->stock < $newQty) {
                return response()->json([
                    'message' => 'Stok tidak mencukupi.',
                ], 422);
            }

            $cart->update([
                'qty'   => $newQty,
                'price' => $product->sell_price * $newQty,
            ]);
        } else {
            Cart::create([
                'cashier_id' => $request->user()->id,
                'product_id' => $product->id,
                'qty'        => $validated['qty'],
                'price'      => $product->sell_price * $validated['qty'],
            ]);
        }

        return response()->json($this->cartPayload($request->user()->id), 201);
    }

    public function updateCartItem(Request $request, Cart $cart)
    {
        $this->authorizeTransactions($request);
        $this->ensureCartOwnership($request, $cart);

        $validated = $request->validate([
            'qty' => ['required', 'integer', 'min:1'],
        ]);

        $product = $cart->product;

        if ($product->stock < $validated['qty']) {
            return response()->json([
                'message' => 'Stok tidak mencukupi.',
            ], 422);
        }

        $cart->update([
            'qty'   => $validated['qty'],
            'price' => $product->sell_price * $validated['qty'],
        ]);

        return response()->json($this->cartPayload($request->user()->id));
    }

    public function removeCartItem(Request $request, Cart $cart)
    {
        $this->authorizeTransactions($request);
        $this->ensureCartOwnership($request, $cart);

        $cart->delete();

        return response()->json($this->cartPayload($request->user()->id));
    }

    public function heldCarts(Request $request)
    {
        $this->authorizeTransactions($request);

        return response()->json([
            'held_carts' => $this->heldCartPayload($request->user()->id),
        ]);
    }

    public function holdCart(Request $request)
    {
        $this->authorizeTransactions($request);

        $validated = $request->validate([
            'label' => ['nullable', 'string', 'max:50'],
        ]);

        $carts = Cart::where('cashier_id', $request->user()->id)
            ->active()
            ->get();

        if ($carts->isEmpty()) {
            return response()->json([
                'message' => 'Keranjang kosong.',
            ], 422);
        }

        $holdId = 'HOLD-' . strtoupper(Str::random(8));
        $label = $validated['label'] ?? 'Transaksi ' . now()->format('H:i');

        Cart::where('cashier_id', $request->user()->id)
            ->active()
            ->update([
                'hold_id'    => $holdId,
                'hold_label' => $label,
                'held_at'    => now(),
            ]);

        return response()->json([
            'message'    => 'Transaksi berhasil ditahan.',
            'held_carts' => $this->heldCartPayload($request->user()->id),
        ]);
    }

    public function resumeHeldCart(Request $request, string $holdId)
    {
        $this->authorizeTransactions($request);

        if (Cart::where('cashier_id', $request->user()->id)->active()->exists()) {
            return response()->json([
                'message' => 'Selesaikan atau tahan transaksi aktif terlebih dahulu.',
            ], 422);
        }

        $heldQuery = Cart::where('cashier_id', $request->user()->id)->forHold($holdId);

        if (! $heldQuery->exists()) {
            return response()->json([
                'message' => 'Transaksi ditahan tidak ditemukan.',
            ], 404);
        }

        $heldQuery->update([
            'hold_id'    => null,
            'hold_label' => null,
            'held_at'    => null,
        ]);

        return response()->json($this->cartPayload($request->user()->id));
    }

    public function clearHeldCart(Request $request, string $holdId)
    {
        $this->authorizeTransactions($request);

        $deleted = Cart::where('cashier_id', $request->user()->id)
            ->forHold($holdId)
            ->delete();

        if ($deleted === 0) {
            return response()->json([
                'message' => 'Transaksi ditahan tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'message'    => 'Transaksi ditahan berhasil dihapus.',
            'held_carts' => $this->heldCartPayload($request->user()->id),
        ]);
    }

    public function checkout(Request $request, PaymentGatewayManager $paymentGatewayManager)
    {
        $this->authorizeTransactions($request);

        $validated = $request->validate([
            'customer_id'     => ['required', 'integer', 'exists:customers,id'],
            'discount'        => ['nullable', 'numeric', 'min:0'],
            'shipping_cost'   => ['nullable', 'numeric', 'min:0'],
            'grand_total'     => ['required', 'numeric', 'min:0'],
            'cash'            => ['nullable', 'numeric', 'min:0'],
            'change'          => ['nullable', 'numeric', 'min:0'],
            'payment_gateway' => ['nullable', 'string', 'in:bank_transfer,midtrans,xendit'],
            'bank_account_id' => ['nullable', 'integer', 'exists:bank_accounts,id'],
            'pay_later'       => ['nullable', 'boolean'],
            'due_date'        => ['nullable', 'date'],
        ]);

        $carts = Cart::with('product')
            ->where('cashier_id', $request->user()->id)
            ->active()
            ->get();

        if ($carts->isEmpty()) {
            return response()->json([
                'message' => 'Keranjang kosong.',
            ], 422);
        }

        $isPayLater = (bool) ($validated['pay_later'] ?? false);
        $paymentGateway = $isPayLater ? null : ($validated['payment_gateway'] ?? null);
        $paymentSetting = null;

        if ($isPayLater && empty($validated['due_date'])) {
            return response()->json([
                'message' => 'Tanggal jatuh tempo wajib diisi untuk nota barang.',
            ], 422);
        }

        if ($paymentGateway) {
            $paymentSetting = PaymentSetting::first();

            if (! $paymentSetting || ! $paymentSetting->isGatewayReady($paymentGateway)) {
                return response()->json([
                    'message' => 'Gateway pembayaran belum dikonfigurasi.',
                ], 422);
            }
        }

        $invoice = 'TRX-' . Str::upper(Str::random(10));
        $isCashPayment = empty($paymentGateway) && ! $isPayLater;
        $cashAmount = $isCashPayment ? (int) ($validated['cash'] ?? 0) : 0;
        $changeAmount = $isCashPayment ? (int) ($validated['change'] ?? 0) : 0;

        $transaction = DB::transaction(function () use (
            $validated,
            $request,
            $invoice,
            $carts,
            $paymentGateway,
            $isPayLater,
            $isCashPayment,
            $cashAmount,
            $changeAmount
        ) {
            $transaction = Transaction::create([
                'cashier_id'      => $request->user()->id,
                'customer_id'     => $validated['customer_id'],
                'invoice'         => $invoice,
                'cash'            => $cashAmount,
                'change'          => $changeAmount,
                'discount'        => (int) ($validated['discount'] ?? 0),
                'shipping_cost'   => (int) ($validated['shipping_cost'] ?? 0),
                'grand_total'     => (int) $validated['grand_total'],
                'payment_method'  => $isPayLater ? 'pay_later' : ($paymentGateway ?: 'cash'),
                'payment_status'  => $isCashPayment ? 'paid' : ($isPayLater ? 'unpaid' : 'pending'),
                'bank_account_id' => $paymentGateway === 'bank_transfer' ? ($validated['bank_account_id'] ?? null) : null,
            ]);

            foreach ($carts as $cart) {
                $transaction->details()->create([
                    'transaction_id' => $transaction->id,
                    'product_id'     => $cart->product_id,
                    'qty'            => $cart->qty,
                    'price'          => $cart->price,
                ]);

                $profits = ($cart->product->sell_price - $cart->product->buy_price) * $cart->qty;

                $transaction->profits()->create([
                    'transaction_id' => $transaction->id,
                    'total'          => $profits,
                ]);

                $cart->product->decrement('stock', $cart->qty);
            }

            Cart::where('cashier_id', $request->user()->id)->active()->delete();

            if ($isPayLater) {
                Receivable::create([
                    'customer_id'    => $validated['customer_id'],
                    'transaction_id' => $transaction->id,
                    'invoice'        => $invoice,
                    'total'          => (int) $validated['grand_total'],
                    'paid'           => 0,
                    'due_date'       => $validated['due_date'],
                    'status'         => 'unpaid',
                ]);
            }

            return $transaction->fresh(['customer', 'details.product', 'receivable', 'bankAccount']);
        });

        if ($paymentGateway) {
            try {
                $paymentResponse = $paymentGatewayManager->createPayment($transaction, $paymentGateway, $paymentSetting);

                $transaction->update([
                    'payment_reference' => $paymentResponse['reference'] ?? null,
                    'payment_url'       => $paymentResponse['payment_url'] ?? null,
                ]);

                $transaction->refresh();
            } catch (PaymentGatewayException $exception) {
                return response()->json([
                    'message' => $exception->getMessage(),
                ], 422);
            }
        }

        return response()->json([
            'message'     => 'Transaksi berhasil dibuat.',
            'transaction' => $this->transactionPayload($transaction),
        ], 201);
    }

    public function transactions(Request $request)
    {
        $this->authorizeTransactions($request);

        $transactions = Transaction::query()
            ->with(['customer:id,name', 'cashier:id,name', 'receivable'])
            ->withSum('details as total_items', 'qty')
            ->withSum('profits as total_profit', 'total')
            ->when(! $request->user()->isSuperAdmin(), function (Builder $query) use ($request) {
                $query->where('cashier_id', $request->user()->id);
            })
            ->when($request->filled('search'), function (Builder $query) use ($request) {
                $query->where('invoice', 'like', '%' . $request->string('search')->trim() . '%');
            })
            ->when($request->filled('start_date'), function (Builder $query) use ($request) {
                $query->whereDate('created_at', '>=', $request->string('start_date'));
            })
            ->when($request->filled('end_date'), function (Builder $query) use ($request) {
                $query->whereDate('created_at', '<=', $request->string('end_date'));
            })
            ->orderByDesc('id')
            ->paginate($request->integer('per_page', 15))
            ->through(function (Transaction $transaction) {
                return [
                    'id'            => $transaction->id,
                    'invoice'       => $transaction->invoice,
                    'customer_name' => $transaction->customer?->name,
                    'cashier_name'  => $transaction->cashier?->name,
                    'grand_total'   => (int) $transaction->grand_total,
                    'payment_method'=> $transaction->payment_method,
                    'payment_status'=> $transaction->payment_status,
                    'total_items'   => (int) ($transaction->total_items ?? 0),
                    'total_profit'  => (int) ($transaction->total_profit ?? 0),
                    'created_at'    => $this->rawDate($transaction->getRawOriginal('created_at')),
                ];
            })
            ->withQueryString();

        return response()->json($transactions);
    }

    public function showTransaction(Request $request, string $invoice)
    {
        $this->authorizeTransactions($request);

        $transaction = Transaction::query()
            ->with(['customer', 'cashier:id,name', 'details.product', 'receivable', 'bankAccount'])
            ->where('invoice', $invoice)
            ->firstOrFail();

        if (! $request->user()->isSuperAdmin() && $transaction->cashier_id !== $request->user()->id) {
            abort(403, 'Anda tidak memiliki akses ke transaksi ini.');
        }

        return response()->json([
            'transaction' => $this->transactionPayload($transaction),
        ]);
    }

    private function authorizeTransactions(Request $request): void
    {
        abort_unless($request->user()->can('transactions-access'), 403, 'Anda tidak memiliki izin untuk mengakses modul transaksi.');
    }

    private function ensureCartOwnership(Request $request, Cart $cart): void
    {
        abort_if($cart->cashier_id !== $request->user()->id, 404);
        abort_if($cart->hold_id !== null, 422, 'Item keranjang sedang ditahan.');
    }

    private function cartPayload(int $userId): array
    {
        $items = Cart::with('product.category:id,name')
            ->where('cashier_id', $userId)
            ->active()
            ->latest()
            ->get()
            ->map(function (Cart $cart) {
                return [
                    'id'        => $cart->id,
                    'qty'       => (int) $cart->qty,
                    'price'     => (int) $cart->price,
                    'product'   => $cart->product,
                    'line_total'=> (int) $cart->price,
                ];
            });

        return [
            'items'      => $items,
            'items_count'=> $items->sum('qty'),
            'subtotal'   => $items->sum('line_total'),
        ];
    }

    private function heldCartPayload(int $userId)
    {
        return Cart::with('product:id,title,sell_price,image')
            ->where('cashier_id', $userId)
            ->held()
            ->get()
            ->groupBy('hold_id')
            ->map(function ($items, $holdId) {
                $first = $items->first();

                return [
                    'hold_id'     => $holdId,
                    'label'       => $first->hold_label,
                    'held_at'     => optional($first->held_at)->toISOString(),
                    'items_count' => (int) $items->sum('qty'),
                    'total'       => (int) $items->sum('price'),
                    'items'       => $items->map(fn (Cart $cart) => [
                        'id'      => $cart->id,
                        'qty'     => (int) $cart->qty,
                        'price'   => (int) $cart->price,
                        'product' => $cart->product,
                    ])->values(),
                ];
            })
            ->values();
    }

    private function transactionPayload(Transaction $transaction): array
    {
        return [
            'id'                => $transaction->id,
            'invoice'           => $transaction->invoice,
            'grand_total'       => (int) $transaction->grand_total,
            'discount'          => (int) $transaction->discount,
            'shipping_cost'     => (int) $transaction->shipping_cost,
            'cash'              => (int) $transaction->cash,
            'change'            => (int) $transaction->change,
            'payment_method'    => $transaction->payment_method,
            'payment_status'    => $transaction->payment_status,
            'payment_reference' => $transaction->payment_reference,
            'payment_url'       => $transaction->payment_url,
            'created_at'        => $this->rawDate($transaction->getRawOriginal('created_at')),
            'customer'          => $transaction->customer,
            'cashier'           => $transaction->cashier,
            'bank_account'      => $transaction->bankAccount,
            'receivable'        => $transaction->receivable,
            'details'           => $transaction->details->map(fn ($detail) => [
                'id'         => $detail->id,
                'qty'        => (int) $detail->qty,
                'price'      => (int) $detail->price,
                'line_total' => (int) $detail->price,
                'product'    => $detail->product,
            ])->values(),
        ];
    }

    private function rawDate($value): ?string
    {
        if (! $value) {
            return null;
        }

        return Carbon::parse($value)->toISOString();
    }
}
