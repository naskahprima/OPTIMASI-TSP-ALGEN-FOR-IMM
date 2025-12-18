<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MatriksJarak extends Model
{
    use HasFactory;
    
    protected $table = "matriks_jaraks";
    
    protected $fillable = [
        "origin_id", "destination_id", "distance"
    ];

    public $timestamps = false;

    // ✅ TAMBAH RELATIONSHIPS!
    public function origin()
    {
        return $this->belongsTo(Destinasi::class, 'origin_id');
    }

    public function destination()
    {
        return $this->belongsTo(Destinasi::class, 'destination_id');
    }
}