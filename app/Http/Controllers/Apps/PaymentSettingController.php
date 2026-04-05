<?php
namespace App\Http\Controllers\Apps;

use App\Http\Controllers\Controller;
use App\Models\PaymentSetting;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class PaymentSettingController extends Controller
{
    public function edit()
    {
        $setting = PaymentSetting::firstOrCreate([], [
            'default_gateway' => 'cash',
        ]);

        $midtransWebhookUrl = route('webhooks.midtrans');
        $xenditWebhookUrl   = route('webhooks.xendit');
        $appUrl             = (string) config('app.url');
        $webhookWarnings    = [];

        if (blank($appUrl)) {
            $webhookWarnings[] = 'APP_URL belum diatur. Webhook URL yang dihasilkan bisa tidak valid untuk Midtrans/Xendit.';
        } elseif ($this->isLocalAppUrl($appUrl)) {
            $webhookWarnings[] = 'APP_URL masih mengarah ke localhost atau 127.0.0.1. Payment gateway membutuhkan URL publik yang bisa diakses dari internet.';
        }

        if ($setting->xendit_enabled && blank($setting->xendit_callback_token) && blank(config('services.xendit.callback_token'))) {
            $webhookWarnings[] = 'Xendit aktif tetapi callback token belum diisi. Webhook Xendit akan ditolak sampai token tersedia.';
        }

        return Inertia::render('Dashboard/Settings/Payment', [
            'setting'           => $setting,
            'supportedGateways' => [
                ['value' => 'cash', 'label' => 'Tunai'],
                ['value' => PaymentSetting::GATEWAY_BANK_TRANSFER, 'label' => 'Transfer Bank'],
                ['value' => PaymentSetting::GATEWAY_MIDTRANS, 'label' => 'Midtrans'],
                ['value' => PaymentSetting::GATEWAY_XENDIT, 'label' => 'Xendit'],
            ],
            'webhookUrls'       => [
                'midtrans' => $midtransWebhookUrl,
                'xendit'   => $xenditWebhookUrl,
            ],
            'webhookWarnings'   => $webhookWarnings,
        ]);
    }

    public function update(Request $request)
    {
        $setting = PaymentSetting::firstOrCreate([], [
            'default_gateway' => 'cash',
        ]);

        $data = $request->validate([
            'default_gateway'       => [
                'required',
                Rule::in(['cash', PaymentSetting::GATEWAY_BANK_TRANSFER, PaymentSetting::GATEWAY_MIDTRANS, PaymentSetting::GATEWAY_XENDIT]),
            ],
            'bank_transfer_enabled' => ['boolean'],
            'midtrans_enabled'      => ['boolean'],
            'midtrans_server_key'   => ['nullable', 'string'],
            'midtrans_client_key'   => ['nullable', 'string'],
            'midtrans_production'   => ['boolean'],
            'xendit_enabled'        => ['boolean'],
            'xendit_secret_key'     => ['nullable', 'string'],
            'xendit_public_key'     => ['nullable', 'string'],
            'xendit_callback_token' => ['nullable', 'string', 'max:255'],
            'xendit_production'     => ['boolean'],
        ]);

        $midtransEnabled = (bool) ($data['midtrans_enabled'] ?? false);
        $xenditEnabled   = (bool) ($data['xendit_enabled'] ?? false);

        if ($midtransEnabled && (empty($data['midtrans_server_key']) || empty($data['midtrans_client_key']))) {
            return back()->withErrors([
                'midtrans_server_key' => 'Server key dan Client key Midtrans wajib diisi saat mengaktifkan Midtrans.',
            ])->withInput();
        }

        if ($xenditEnabled && empty($data['xendit_secret_key'])) {
            return back()->withErrors([
                'xendit_secret_key' => 'Secret key Xendit wajib diisi saat mengaktifkan Xendit.',
            ])->withInput();
        }

        if ($xenditEnabled && empty($data['xendit_callback_token'])) {
            return back()->withErrors([
                'xendit_callback_token' => 'Callback token Xendit wajib diisi saat mengaktifkan Xendit.',
            ])->withInput();
        }

        if (
            $data['default_gateway'] !== 'cash'
            && ! (($data['default_gateway'] === PaymentSetting::GATEWAY_MIDTRANS && $midtransEnabled)
                || ($data['default_gateway'] === PaymentSetting::GATEWAY_XENDIT && $xenditEnabled))
        ) {
            return back()->withErrors([
                'default_gateway' => 'Gateway default harus dalam kondisi aktif.',
            ])->withInput();
        }

        $setting->update([
            'default_gateway'       => $data['default_gateway'],
            'bank_transfer_enabled' => (bool) ($data['bank_transfer_enabled'] ?? false),
            'midtrans_enabled'      => $midtransEnabled,
            'midtrans_server_key'   => $data['midtrans_server_key'],
            'midtrans_client_key'   => $data['midtrans_client_key'],
            'midtrans_production'   => (bool) ($data['midtrans_production'] ?? false),
            'xendit_enabled'        => $xenditEnabled,
            'xendit_secret_key'     => $data['xendit_secret_key'],
            'xendit_public_key'     => $data['xendit_public_key'],
            'xendit_callback_token' => $data['xendit_callback_token'],
            'xendit_production'     => (bool) ($data['xendit_production'] ?? false),
        ]);

        return redirect()
            ->route('settings.payments.edit')
            ->with('success', 'Konfigurasi payment gateway berhasil disimpan.');
    }

    private function isLocalAppUrl(string $appUrl): bool
    {
        $host = parse_url($appUrl, PHP_URL_HOST);

        return in_array($host, ['localhost', '127.0.0.1'], true)
            || str_ends_with((string) $host, '.test');
    }
}
