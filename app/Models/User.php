<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'users';
    protected $primaryKey = 'users_id'; // Primary key berupa VARCHAR
    public $incrementing = false;       // Matikan auto increment
    protected $keyType = 'string';
    public $timestamps = false;         // Menggunakan users_acc_created manual

    protected $fillable = [
        'users_id',
        'users_name',
        'users_password',
        'users_role',
        'users_kota',
        'users_email',
        'users_acc_created',
        'status_del'
    ];

    protected $hidden = [
        'users_password',
    ];

    /**
     * Memberitahu Laravel bahwa password autentikasi disimpan di kolom 'users_password'
     */
    public function getAuthPassword()
    {
        return $this->users_password;
    }
}