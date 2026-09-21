<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends BaseTestCase
{
    /**
     * Disable SQLite foreign key enforcement.
     * Call this at the start of setUp() AFTER parent::setUp() for tests
     * that use dummy IDs not present in related tables.
     *
     * Note: RefreshDatabase wraps each test in a transaction.
     * We must execute the PRAGMA within the active connection / transaction.
     */
    protected function disableForeignKeyConstraints(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            DB::unprepared('PRAGMA foreign_keys = OFF');
        }
    }
}
