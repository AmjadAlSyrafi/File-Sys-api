<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FileUserPermission extends Model
{
    use HasFactory;

    protected $fillable = [
        'file_id',
        'user_id',
        'permissions',
    ];

    /**
     * Relationship with the File model.
     */
    public function file()
    {
        return $this->belongsTo(File::class);
    }

    /**
     * Relationship with the User model.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
