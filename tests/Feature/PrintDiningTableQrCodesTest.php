<?php

namespace Tests\Feature;

use App\Models\DiningTable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PrintDiningTableQrCodesTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_generates_printable_qr_codes_for_active_tables_only(): void
    {
        Storage::fake('local');
        config(['app.qr_base_url' => 'http://koka-cafe.test']);

        DiningTable::factory()->create([
            'number' => '21',
            'label' => 'Meja Teras',
        ]);
        DiningTable::factory()->create([
            'number' => '22',
            'label' => 'Meja Dalam',
        ]);
        DiningTable::factory()->create([
            'number' => '23',
            'label' => 'Meja Nonaktif',
            'is_active' => false,
        ]);

        $this->artisan('koka:qr-tables')
            ->assertSuccessful();

        $this->assertTrue(Storage::disk('local')->exists('qr-meja.html'));
        $printSheet = Storage::disk('local')->get('qr-meja.html');

        $this->assertStringContainsString('Meja Teras', $printSheet);
        $this->assertStringContainsString('Meja Dalam', $printSheet);
        $this->assertStringNotContainsString('Meja Nonaktif', $printSheet);
        $this->assertSame(2, substr_count($printSheet, 'class="qr-image"'));
        $this->assertStringContainsString('data:image/svg+xml;base64,', $printSheet);
        $this->assertStringContainsString('URL saat ini', $printSheet);
    }

    public function test_command_rejects_an_invalid_base_url(): void
    {
        Storage::fake('local');
        config(['app.qr_base_url' => 'javascript:alert(1)']);
        DiningTable::factory()->create(['number' => '23']);

        $this->artisan('koka:qr-tables')
            ->assertFailed();

        $this->assertFalse(Storage::disk('local')->exists('qr-meja.html'));
    }
}
