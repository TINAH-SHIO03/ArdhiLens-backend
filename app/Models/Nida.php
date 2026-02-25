<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class Nida extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nin',
        'first_name',
        'middle_name',
        'surname',
        'gender',
        'date_of_birth',
        'nationality',
        'birth_certificate_number',
        'passport_number',
        'passport_image_path',
        'voter_id',
        'marital_status',
        'occupation',
        'father_first_name',
        'father_middle_name',
        'father_surname',
        'mother_first_name',
        'mother_middle_name',
        'mother_surname',
        'highest_education',
        'res_region',
        'res_district',
        'res_ward',
        'res_mtaa',
        'res_postcode',
        'perm_region',
        'perm_district',
        'perm_ward',
        'perm_mtaa',
        'phone_number',
        'photo_base64',
        'signature_base64',
        'status',
        'issued_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date_of_birth' => 'date',
        'issued_at' => 'datetime',
        'status' => 'string', 
    ];

    /*
    |--------------------------------------------------------------------------
    | Accessors & Mutators
    |--------------------------------------------------------------------------
    */

    /**
     * Get the user's full name.
     */
    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->middle_name} {$this->surname}");
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes (For Cleaner Queries)
    |--------------------------------------------------------------------------
    */

    /**
     * Scope a query to search by NIN.
     */
    public function scopeByNin(Builder $query, string $nin): Builder
    {
        return $query->where('nin', $nin);
    }

    /**
     * Scope a query to search by full name (fuzzy search).
     */
    public function scopeSearchName(Builder $query, string $name): Builder
    {
        return $query->where('first_name', 'LIKE', "%{$name}%")
                     ->orWhere('surname', 'LIKE', "%{$name}%");
    }

    /**
     * Scope a query to filter by status.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'Active');
    }
    
    public function user()
{
    return $this->hasOne(User::class, 'nin', 'nin');
}
}
