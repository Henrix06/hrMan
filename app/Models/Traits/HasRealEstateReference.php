<?php

namespace App\Models\Traits;

trait HasRealEstateReference
{
    public static function generateReference(string $prefix): string
    {
        $year = date('Y');
        $count = static::whereYear('created_at', $year)->count() + 1;
        return $prefix . '-' . $year . '-' . str_pad($count, 5, '0', STR_PAD_LEFT);
    }
}
