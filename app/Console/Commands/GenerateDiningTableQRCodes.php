<?php

namespace App\Console\Commands;

use App\Models\DiningTable;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

#[Signature('koka:qr-tables {--base-url= : URL dasar yang dapat dibuka dari ponsel pelanggan} --base-url hanya mengatur URL; semua token tetap diambil dari meja aktif.')]
#[Description('Buat lembar cetak QR untuk semua meja KOKA yang aktif.')]
class GenerateDiningTableQRCodes extends Command
{
    public function handle(): int
    {
        $baseUrl = rtrim(trim((string) ($this->option('base-url') ?: config('app.qr_base_url'))), '/');
        $urlParts = parse_url($baseUrl);

        if (
            filter_var($baseUrl, FILTER_VALIDATE_URL) === false
            || ! in_array($urlParts['scheme'] ?? null, ['http', 'https'], true)
            || empty($urlParts['host'])
            || isset($urlParts['user'])
            || isset($urlParts['pass'])
        ) {
            $this->error('QR_BASE_URL harus berupa URL http atau https yang valid tanpa kredensial.');

            return self::FAILURE;
        }

        $tables = DiningTable::query()
            ->where('is_active', true)
            ->orderBy('number')
            ->get();

        if ($tables->isEmpty()) {
            $this->error('Tidak ada meja aktif untuk dibuatkan QR.');

            return self::FAILURE;
        }

        $options = new QROptions;
        $options->outputBase64 = true;
        $options->svgAddXmlHeader = false;
        $options->scale = 8;

        $qrCodes = [];

        foreach ($tables as $table) {
            $tableUrl = $baseUrl.'/meja/'.$table->qr_token;
            $qrCodes[$table->id] = (new QRCode($options))->render($tableUrl);
        }

        $host = strtolower((string) $urlParts['host']);
        $isLocalOnly = in_array($host, ['localhost', '127.0.0.1'], true) || str_ends_with($host, '.test');
        $html = view('print.dining-tables', compact('baseUrl', 'isLocalOnly', 'qrCodes', 'tables'))->render();

        Storage::disk('local')->put('qr-meja.html', $html);

        $this->info('Lembar QR berhasil dibuat untuk '.$tables->count().' meja aktif.');
        $this->line(Storage::disk('local')->path('qr-meja.html'));

        if ($isLocalOnly) {
            $this->warn('URL dasar hanya dapat dibuka oleh perangkat yang mengenali host '.$host.'.');
            $this->line('Untuk scan lewat ponsel, jalankan ulang dengan --base-url berisi IP LAN atau domain yang bisa dijangkau ponsel.');
        }

        return self::SUCCESS;
    }
}
