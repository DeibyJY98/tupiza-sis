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
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'username',
        'email',
        'password',
        'foto',
        'estado',
        'id_rol',
        'id_persona',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function rol(){
        return $this->belongsTo(Rol::class,'id_rol'); //uno a muchos, solo tiene 1
    }

    public function persona(){
        return $this->belongsTo(Persona::class,'id_persona'); //uno a muchos, solo tiene 1
    }
    
    public function toShow(){
        return[
            'id'        => $this->id,
            'username'  => $this->username,
            'email'     => $this->email,
            'foto'      => $this->foto,
            'estado'    => $this->estado,
            'rol'       => $this->rol->nombre ?? null,
            'nombre'    => $this->persona->nombre ?? null,
            'apellido'  => $this->persona->apellido ?? null
        ];
    }

}
