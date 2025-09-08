<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE activities ADD CONSTRAINT activities_level_range CHECK (level >= 1 AND level <= 3)");
        DB::statement("ALTER TABLE activities ADD CONSTRAINT activities_parent_level_consistency CHECK ((level = 1 AND parent_id IS NULL) OR (level > 1 AND parent_id IS NOT NULL))");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE activities DROP CONSTRAINT IF EXISTS activities_parent_level_consistency");
        DB::statement("ALTER TABLE activities DROP CONSTRAINT IF EXISTS activities_level_range");
    }
};

