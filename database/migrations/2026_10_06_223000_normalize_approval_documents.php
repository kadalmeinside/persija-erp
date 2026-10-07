<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (in_array(DB::getDriverName(), ['mysql', 'pgsql'], true)) {
            DB::statement(
                'ALTER TABLE tbl_approval_process DROP CONSTRAINT approval_process_one_document_chk'
            );
        }

        Schema::create('tbl_approval_documents', function (Blueprint $table) {
            $table->id();
            $table->enum('document_type', ['Pengajuan', 'Cuti', 'Pinjaman', 'Invoice']);
            $table->unsignedBigInteger('document_id');
            $table->timestamps();
            $table->unique(['document_type', 'document_id']);
        });

        foreach ([
            'pengajuan' => 'tbl_pengajuan_header',
            'cuti' => 'tbl_pengajuan_cuti',
            'pinjaman' => 'tbl_pinjaman',
            'invoice' => 'tbl_invoice_header',
        ] as $suffix => $tableName) {
            Schema::create("tbl_approval_document_{$suffix}", function (Blueprint $table) use ($tableName) {
                $table->unsignedBigInteger('id_approval_document')->primary();
                $table->unsignedBigInteger('document_id')->unique();
                $table->foreign('id_approval_document')->references('id')->on('tbl_approval_documents')->cascadeOnDelete();
                $table->foreign('document_id')->references('id')->on($tableName)->cascadeOnDelete();
            });
        }

        Schema::table('tbl_approval_process', function (Blueprint $table) {
            $table->foreignId('id_approval_document')
                ->after('id')
                ->nullable()
                ->constrained('tbl_approval_documents')
                ->cascadeOnDelete();
        });

        $legacyRows = DB::table('tbl_approval_process')
            ->select(['id', 'id_pengajuan', 'id_cuti', 'id_pinjaman', 'id_invoice'])
            ->get();

        foreach ($legacyRows as $row) {
            $references = collect([
                'Pengajuan' => $row->id_pengajuan,
                'Cuti' => $row->id_cuti,
                'Pinjaman' => $row->id_pinjaman,
                'Invoice' => $row->id_invoice,
            ])->filter(fn ($id) => $id !== null);

            if ($references->count() !== 1) {
                throw new RuntimeException(
                    "Approval process {$row->id} harus memiliki tepat satu referensi dokumen saat backfill."
                );
            }

            [$type, $documentId] = [$references->keys()->first(), $references->first()];
            $registry = DB::table('tbl_approval_documents')
                ->where('document_type', $type)
                ->where('document_id', $documentId)
                ->first();
            $registryId = $registry?->id;
            if (!$registryId) {
                $registryId = DB::table('tbl_approval_documents')->insertGetId([
                    'document_type' => $type,
                    'document_id' => $documentId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $linkTable = 'tbl_approval_document_' . strtolower($type);
            DB::table($linkTable)->insertOrIgnore([
                'id_approval_document' => $registryId,
                'document_id' => $documentId,
            ]);
            DB::table('tbl_approval_process')
                ->where('id', $row->id)
                ->update(['id_approval_document' => $registryId]);
        }

        if (DB::table('tbl_approval_process')->whereNull('id_approval_document')->exists()) {
            throw new RuntimeException('Backfill approval document tidak lengkap; migration dibatalkan.');
        }

        Schema::table('tbl_approval_process', function (Blueprint $table) {
            $table->unsignedBigInteger('id_approval_document')->nullable(false)->change();
            foreach (['id_pengajuan', 'id_cuti', 'id_pinjaman', 'id_invoice'] as $column) {
                $table->dropForeign([$column]);
                $table->dropColumn($column);
            }
        });
    }

    public function down(): void
    {
        Schema::table('tbl_approval_process', function (Blueprint $table) {
            $table->dropForeign(['id_approval_document']);
            $table->dropColumn('id_approval_document');
            $table->foreignId('id_pengajuan')->nullable()->constrained('tbl_pengajuan_header')->cascadeOnDelete();
            $table->foreignId('id_cuti')->nullable()->constrained('tbl_pengajuan_cuti')->cascadeOnDelete();
            $table->foreignId('id_pinjaman')->nullable()->constrained('tbl_pinjaman')->cascadeOnDelete();
            $table->foreignId('id_invoice')->nullable()->constrained('tbl_invoice_header')->cascadeOnDelete();
        });

        foreach (['pengajuan', 'cuti', 'pinjaman', 'invoice'] as $suffix) {
            Schema::dropIfExists("tbl_approval_document_{$suffix}");
        }
        Schema::dropIfExists('tbl_approval_documents');
    }
};
