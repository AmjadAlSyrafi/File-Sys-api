<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Share extends Model
{
    /** @use HasFactory<\Database\Factories\ShareFactory> */
    use HasFactory;

    public function user()
    {
        return $this->belongsTo(User::class, 'shared_with_user_id');
    }

}
