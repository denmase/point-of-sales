<?php

namespace App\Http\Middleware;

use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): string|null
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $lowStockNotifications = [];

        if ($request->user()) {
            $userId = $request->user()->id;
            $lowStockNotifications = Product::where('stock', '<=', 0)
                ->whereNotExists(function ($query) use ($userId) {
                    $query->selectRaw(1)
                        ->from('product_notification_reads as pr')
                        ->whereColumn('pr.product_id', 'products.id')
                        ->where('pr.user_id', $userId)
                        // Only hide if the notification was read after the last product update
                        ->whereColumn('pr.updated_at', '>=', 'products.updated_at');
                })
                ->orderByDesc('updated_at')
                ->limit(10)
                ->get(['id', 'title', 'stock', 'updated_at'])
                ->map(function ($product) {
                    return [
                        'id'    => $product->id,
                        'title' => $product->title,
                        'stock' => (int) $product->stock,
                        'time'  => optional($product->updated_at)->diffForHumans(),
                    ];
                });
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
                'permissions' => $request->user() ? $request->user()->getPermissions() : [],
                'super' => $request->user() ? $request->user()->isSuperAdmin() : false,
            ],
            'lowStockNotifications' => $lowStockNotifications,
        ];
    }
}
