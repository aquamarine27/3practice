<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cms_blocks', function (Blueprint $table) {
            
            
            if (!Schema::hasColumn('cms_blocks', 'title')) {
                $table->string('title')->nullable()->after('slug'); 
            }

            if (!Schema::hasColumn('cms_blocks', 'content')) {
                $table->text('content')->nullable()->after('title');
            }
            
            if (!Schema::hasColumn('cms_blocks', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('content');
            }
        });
        
  
        if (DB::table('cms_blocks')->where('slug', 'dashboard_experiment')->count() === 0) {
            DB::table('cms_blocks')->insert([
                'slug' => 'dashboard_experiment',
                'title' => 'Экспериментальный блок',
                'content' => 'Этот блок загружен из БД прикинь',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
    }
};