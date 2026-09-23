<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('question_categories', function (Blueprint $table) {
            $table->dropUnique('question_categories_name_unique');
            $table->unique(['owner_id', 'name'], 'question_categories_owner_name_unique');
        });
    }

    public function down(): void
    {
        Schema::table('question_categories', function (Blueprint $table) {
            $table->dropUnique('question_categories_owner_name_unique');
        });

        DB::table('question_categories')
            ->select('name')
            ->groupBy('name')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('name')
            ->each(function (string $name) {
                DB::table('question_categories')
                    ->where('name', $name)
                    ->orderBy('id')
                    ->get(['id', 'owner_id'])
                    ->skip(1)
                    ->each(function ($category) use ($name) {
                        $suffix = ' (Owner '.($category->owner_id ?? 'Global').')';
                        DB::table('question_categories')
                            ->where('id', $category->id)
                            ->update(['name' => mb_substr($name, 0, 255 - mb_strlen($suffix)).$suffix]);
                    });
            });

        Schema::table('question_categories', function (Blueprint $table) {
            $table->unique('name');
        });
    }
};
