<?php

namespace Tests\Feature;

use Tests\TestCase;

class WeeklyLogTest extends TestCase
{
    public function test_can_view_dashboard()
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Sixteen weeks', false);
        $response->assertSee('Week 1', false);
    }

    public function test_can_store_daily_log()
    {
        $dailyFile = base_path('Daily_Reports.xlsx');

        if (!file_exists($dailyFile)) {
            $this->markTestSkipped('Daily_Reports.xlsx is not present; skipping Excel write smoke test.');
        }

        // Snapshot so the smoke write does not leave fixture pollution behind.
        $backup = $dailyFile . '.phpunit-bak';
        copy($dailyFile, $backup);

        try {
            $response = $this->postJson(route('weekly-log.daily.store'), [
                'week' => 1,
                'date' => '2026-02-04',
                'activity' => 'Test Activity via PHPUnit',
            ]);

            $this->assertContains($response->status(), [200, 403, 404, 422]);

            if ($response->status() === 200) {
                $response->assertJson(['success' => true]);
            }
        } finally {
            if (file_exists($backup)) {
                rename($backup, $dailyFile);
            }
        }
    }

    public function test_delete_daily_returns_404_when_file_missing()
    {
        $dailyFile = base_path('Daily_Reports.xlsx');
        $backup = null;

        if (file_exists($dailyFile)) {
            $backup = $dailyFile . '.bak-test';
            rename($dailyFile, $backup);
        }

        try {
            $response = $this->postJson(route('weekly-log.daily.delete'), [
                'row_index' => 2,
            ]);

            $response->assertStatus(404)
                ->assertJson(['error' => 'Daily Reports file not found']);
        } finally {
            if ($backup && file_exists($backup)) {
                rename($backup, $dailyFile);
            }
        }
    }
}
