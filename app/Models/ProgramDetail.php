<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProgramDetail extends Model
{
    protected $fillable = ['program_id', 'modelo', 'cantidad_solicitada'];
    
    public function program()
    {
        return $this->belongsTo(Program::class);
    }
    
    public function getTotalPiecesAttribute()
    {
        $product = Product::where('modelo', $this->modelo)->first();
        return $product ? $this->cantidad_solicitada * $product->piezas : 0;
    }
    
    public function getTotalTimeAttribute()
    {
        $product = Product::where('modelo', $this->modelo)->first();
        return $product ? $this->cantidad_solicitada * $product->tiempo : 0;
    }
    
    public function getProductInfo()
    {
        return Product::where('modelo', $this->modelo)
            ->with('workCenter')
            ->first();
    }
}
