<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partner_types', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $now = now();
        DB::table('partner_types')->insert([
            ['code' => 'rental', 'name' => 'Rental', 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'heavy_equipment_contractor', 'name' => 'Kontraktor Alat Berat', 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'mining_contractor', 'name' => 'Kontraktor Tambang', 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'kso', 'name' => 'Kerja Sama Operasi (KSO)', 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'transport_contractor', 'name' => 'Kontraktor Transportasi', 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'service_vendor', 'name' => 'Penyedia Jasa', 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'supplier', 'name' => 'Pemasok', 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'other', 'name' => 'Lainnya', 'created_at' => $now, 'updated_at' => $now],
        ]);

        Schema::table('partners', function (Blueprint $table) {
            $table->foreignId('owner_id')->nullable()->after('id')->constrained('partners')->restrictOnDelete();
            $table->string('organization_kind')->default('partner')->after('owner_id')->index();
            $table->foreignId('partner_type_id')->nullable()->after('organization_kind')->constrained()->restrictOnDelete();
            $table->string('phone')->nullable()->after('email');
            $table->text('address')->nullable()->after('phone');
            $table->string('site_name')->nullable()->after('address');
            $table->string('emergency_phone')->nullable()->after('site_name');
            $table->string('permit_prefix', 30)->nullable()->after('emergency_phone');
            $table->string('logo_path')->nullable()->after('permit_prefix');
        });

        $rentalTypeId = DB::table('partner_types')->where('code', 'rental')->value('id');
        $otherTypeId = DB::table('partner_types')->where('code', 'other')->value('id');

        DB::table('partners')->where('level', 'owner')->update([
            'organization_kind' => 'owner',
            'partner_type_id' => null,
        ]);
        DB::table('partners')->where('level', 'rental')->update(['partner_type_id' => $rentalTypeId]);
        DB::table('partners')->whereNot('level', 'owner')->whereNull('partner_type_id')->update([
            'partner_type_id' => $otherTypeId,
        ]);

        foreach (DB::table('partners')->where('organization_kind', 'owner')->get(['id', 'short_name']) as $owner) {
            DB::table('partners')->where('id', $owner->id)->update(['permit_prefix' => $owner->short_name]);
        }

        DB::table('partners')->update(['status' => DB::raw('LOWER(status)')]);
        DB::table('partners')->whereIn('status', ['suspend', 'disabled'])->update([
            'status' => DB::raw("CASE WHEN status = 'suspend' THEN 'suspended' ELSE 'inactive' END"),
        ]);
        DB::table('partners')->where('status', 'slow')->update(['status' => 'slowdown']);
    }

    public function down(): void
    {
        Schema::table('partners', function (Blueprint $table) {
            $table->dropForeign(['owner_id']);
            $table->dropForeign(['partner_type_id']);
            $table->dropIndex(['organization_kind']);
            $table->dropColumn([
                'owner_id',
                'organization_kind',
                'partner_type_id',
                'phone',
                'address',
                'site_name',
                'emergency_phone',
                'permit_prefix',
                'logo_path',
            ]);
        });

        Schema::dropIfExists('partner_types');
    }
};
