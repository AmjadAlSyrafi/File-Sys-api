<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FolderUserPermission extends Model
{
    protected $fillable = ['folder_id', 'user_id', 'permissions'];

    public function folder()
    {
        return $this->belongsTo(Folder::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
