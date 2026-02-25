<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();
        if (Schema::hasTable('users')) {
            Schema::drop('users');
        }
        Schema::enableForeignKeyConstraints();

        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('username', 50)->unique('username');
            $table->string('password', 255);
            $table->string('nama_lengkap', 100)->nullable();
            $table->enum('level', ['admin', 'siswa'])->default('siswa');
            $table->string('telegram_chat_id', 255)->nullable();
            $table->enum('status', ['aktif', 'nonaktif'])->default('aktif');
        });

        if (! Schema::hasTable('tokens')) {
            Schema::create('tokens', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('token', 100)->unique();
                $table->enum('status', ['active', 'used', 'expired'])->nullable();
                $table->dateTime('generated_at')->useCurrent();
                $table->dateTime('expired_at')->nullable();
            });
        }

        if (! Schema::hasTable('geo_location')) {
            Schema::create('geo_location', function (Blueprint $table) {
                $table->increments('id');
                $table->string('nama_lokasi', 100);
                $table->decimal('latitude', 10, 8);
                $table->decimal('longitude', 11, 8);
                $table->integer('radius')->comment('radius dalam meter');
                $table->boolean('is_active')->default(true);
                $table->timestamp('created_at')->nullable()->useCurrent();
                $table->timestamp('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();
            });
        }

        if (! Schema::hasTable('hari_libur')) {
            Schema::create('hari_libur', function (Blueprint $table) {
                $table->increments('id');
                $table->date('tanggal');
                $table->string('keterangan', 255)->comment('Contoh: Libur Tahun Baru, Cuti Bersama, Libur Kantor');
                $table->enum('jenis', ['nasional', 'kantor', 'cuti_bersama'])->default('kantor')->comment('Jenis libur untuk kategori');
                $table->unsignedInteger('created_by')->nullable();
                $table->timestamp('created_at')->nullable()->useCurrent();
                $table->timestamp('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();
                $table->unique('tanggal', 'tanggal');
                $table->index('tanggal', 'idx_tanggal');
                $table->index('jenis', 'idx_jenis');
                $table->index('created_by', 'created_by');

                $table->foreign('created_by', 'hari_libur_ibfk_1')->references('id')->on('users')->nullOnDelete();
            });
        }

        if (! Schema::hasTable('izin_pulang')) {
            Schema::create('izin_pulang', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('user_id');
                $table->date('tanggal');
                $table->time('jam_pulang_diajukan')->comment('Jam pulang yang diinginkan');
                $table->text('alasan');
                $table->enum('status_approval', ['pending', 'disetujui', 'ditolak'])->default('pending');
                $table->unsignedInteger('approved_by')->nullable();
                $table->string('catatan_admin', 255)->nullable();
                $table->timestamp('created_at')->nullable()->useCurrent();
                $table->timestamp('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();
                $table->unique(['user_id', 'tanggal'], 'unique_user_date');
                $table->index('tanggal', 'idx_tanggal');
                $table->index('status_approval', 'idx_status');
                $table->index('approved_by', 'approved_by');

                $table->foreign('user_id', 'izin_pulang_ibfk_1')->references('id')->on('users')->cascadeOnDelete();
                $table->foreign('approved_by', 'izin_pulang_ibfk_2')->references('id')->on('users')->nullOnDelete();
            });
        }

        if (! Schema::hasTable('izin_siswa')) {
            Schema::create('izin_siswa', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('user_id');
                $table->date('tanggal');
                $table->text('alasan_siswa')->comment('Alasan dari siswa saat mengajukan');
                $table->string('bukti_file', 255)->nullable()->comment('Path ke file bukti (surat dokter, dll)');
                $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
                $table->text('admin_notes')->nullable()->comment('Catatan/alasan dari admin saat approve/reject');
                $table->unsignedInteger('approved_by')->nullable()->comment('ID admin yang approve/reject');
                $table->timestamp('approved_at')->nullable();
                $table->timestamp('created_at')->nullable()->useCurrent();
                $table->timestamp('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();
                $table->index(['user_id', 'tanggal'], 'idx_user_tanggal');
                $table->index('status', 'idx_status');
                $table->index('tanggal', 'idx_tanggal');
                $table->index('approved_by', 'approved_by');

                $table->foreign('user_id', 'izin_siswa_ibfk_1')->references('id')->on('users')->cascadeOnDelete();
                $table->foreign('approved_by', 'izin_siswa_ibfk_2')->references('id')->on('users')->nullOnDelete();
            });
        }

        if (! Schema::hasTable('wali_siswa')) {
            Schema::create('wali_siswa', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('user_id')->unique('unique_user_wali');
                $table->string('nama_wali', 255);
                $table->string('telegram_chat_id', 50);
                $table->string('hubungan_keluarga', 50)->default('Orang Tua');
                $table->timestamp('created_at')->nullable()->useCurrent();
                $table->timestamp('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();

                $table->foreign('user_id', 'wali_siswa_ibfk_1')->references('id')->on('users')->cascadeOnDelete();
            });
        }

        if (! Schema::hasTable('laporan_bulanan_log')) {
            Schema::create('laporan_bulanan_log', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('user_id');
                $table->unsignedInteger('wali_siswa_id');
                $table->integer('bulan');
                $table->integer('tahun');
                $table->integer('total_hari_kerja')->default(0);
                $table->integer('total_hadir')->default(0);
                $table->integer('total_terlambat')->default(0);
                $table->integer('total_tidak_hadir')->default(0);
                $table->decimal('persentase_kehadiran', 5, 2)->default(0);
                $table->string('telegram_status', 20)->default('pending');
                $table->text('telegram_response')->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->timestamp('created_at')->nullable()->useCurrent();
                $table->index(['bulan', 'tahun'], 'idx_bulan_tahun');
                $table->index(['user_id', 'bulan', 'tahun'], 'idx_user_bulan');
                $table->index('wali_siswa_id', 'wali_siswa_id');

                $table->foreign('user_id', 'laporan_bulanan_log_ibfk_1')->references('id')->on('users')->cascadeOnDelete();
                $table->foreign('wali_siswa_id', 'laporan_bulanan_log_ibfk_2')->references('id')->on('wali_siswa')->cascadeOnDelete();
            });
        }

        if (! Schema::hasTable('registration_tokens')) {
            Schema::create('registration_tokens', function (Blueprint $table) {
                $table->increments('id');
                $table->string('token', 64)->unique();
                $table->string('nama_siswa', 100);
                $table->text('keterangan')->nullable();
                $table->integer('max_uses')->default(1);
                $table->integer('uses_count')->default(0);
                $table->timestamp('created_at')->nullable()->useCurrent();
                $table->timestamp('expires_at');
                $table->boolean('is_used')->default(false);
                $table->timestamp('used_at')->nullable();
                $table->unsignedInteger('used_by_user_id')->nullable();
                $table->unsignedInteger('created_by_admin_id');
                $table->index('token', 'idx_token');
                $table->index('is_used', 'idx_is_used');
                $table->index('expires_at', 'idx_expires_at');
                $table->index('used_by_user_id', 'used_by_user_id');
                $table->index('created_by_admin_id', 'created_by_admin_id');

                $table->foreign('used_by_user_id', 'registration_tokens_ibfk_1')->references('id')->on('users')->nullOnDelete();
                $table->foreign('created_by_admin_id', 'registration_tokens_ibfk_2')->references('id')->on('users')->cascadeOnDelete();
            });
        }

        if (! Schema::hasTable('absensi')) {
            Schema::create('absensi', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('user_id');
                $table->unsignedBigInteger('token_id')->nullable();
                $table->date('tanggal');
                $table->time('waktu');
                $table->enum('status', ['hadir', 'terlambat', 'pulang', 'pulang_izin', 'izin']);
                $table->index('user_id', 'user_id');
                $table->index('token_id', 'token_id');

                $table->foreign('user_id', 'absensi_ibfk_1')->references('id')->on('users')->cascadeOnDelete();
                $table->foreign('token_id', 'fk_absensi_token')->references('id')->on('tokens');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('absensi');
        Schema::dropIfExists('registration_tokens');
        Schema::dropIfExists('laporan_bulanan_log');
        Schema::dropIfExists('wali_siswa');
        Schema::dropIfExists('izin_siswa');
        Schema::dropIfExists('izin_pulang');
        Schema::dropIfExists('hari_libur');
        Schema::dropIfExists('geo_location');
        Schema::dropIfExists('tokens');
        Schema::dropIfExists('users');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });
    }
};
