<?php

namespace Tests\Feature;

use App\Models\DiningTable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiningTableEntryTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_qr_token_links_the_customer_session_to_an_active_table(): void
    {
        $table = DiningTable::factory()->create(['number' => '07']);

        $this->get('/meja/'.$table->qr_token)
            ->assertRedirect('/menu')
            ->assertSessionHas('dining_table_id', $table->id);
    }

    public function test_disabled_and_unknown_qr_tokens_are_rejected(): void
    {
        $table = DiningTable::factory()->create([
            'number' => '08',
            'is_active' => false,
        ]);

        $this->get('/meja/'.str_repeat('x', 48))->assertNotFound();
        $this->get('/meja/'.$table->qr_token)->assertNotFound();
    }
}
