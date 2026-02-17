<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RestockRequest extends Model
{
    protected $table = 'restock_requests';

    protected $fillable = [
        'from_warehouse_id',
        'to_warehouse_id',
        'requested_by',
        'approved_by',
        'transfer_id',
        'status',
        'telegram_token',
        'date',
        'notes',
        'items',
    ];

    protected $casts = [
        'items' => 'array',
        'date' => 'date',
    ];
}
