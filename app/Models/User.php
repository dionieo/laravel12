<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Nonaktifkan remember_token karena kolom tidak ada di tabel users.
     */
    protected $rememberTokenName = null;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'username',
        'password',
        'nama_lengkap',
        'level',
        'telegram_chat_id',
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            // Tidak auto-hash — password lama bisa MD5/plain,
            // re-hash dilakukan di LoginController saat login pertama.
        ];
    }

    public function isAdmin(): bool
    {
        return $this->level === 'admin';
    }

    public function isSiswa(): bool
    {
        return $this->level === 'siswa';
    }

    public function isActive(): bool
    {
        return $this->status === 'aktif';
    }
}
