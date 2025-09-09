<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE service_requests DROP CONSTRAINT IF EXISTS service_requests_status_check");
        DB::statement("ALTER TABLE service_requests ADD CONSTRAINT service_requests_status_check CHECK (status IN ('pending','in_progress','completed','cancelled','urgent'))");
    }

    public function down(): void
    {
        DB::table('service_requests')->where('status', 'urgent')->update(['status' => 'pending']);
        DB::statement("ALTER TABLE service_requests DROP CONSTRAINT IF EXISTS service_requests_status_check");
        DB::statement("ALTER TABLE service_requests ADD CONSTRAINT service_requests_status_check CHECK (status IN ('pending','in_progress','completed','cancelled'))");
    }
};