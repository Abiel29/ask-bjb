<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $fillable = [
        'name',
        'description',
        'industry',
        'visi',
        'misi',
        'employees_count',
        'company_featured_employees_collection',
        'company_similar_collection'
    ];

    // Casting kolom JSON ke array otomatis
    protected $casts = [
        'company_featured_employees_collection' => 'array',
        'company_similar_collection' => 'array',
    ];

}
