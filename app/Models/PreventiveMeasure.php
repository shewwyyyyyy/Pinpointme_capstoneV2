<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PreventiveMeasure extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'author',
        'thumbnail',
        'thumbnail_url',
        'video_url',
        'video_path',
        'category',
        'is_active',
        'is_published',
        'sort_order',
        'duration',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_published' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected $appends = ['is_published'];

    /**
     * Get is_published attribute (alias for is_active)
     */
    public function getIsPublishedAttribute()
    {
        return $this->is_active;
    }

    /**
     * Set is_published attribute (alias for is_active)
     */
    public function setIsPublishedAttribute($value)
    {
        $this->attributes['is_active'] = $value;
    }

    /**
     * Scope to get only active measures
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to order by sort order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderByDesc('created_at');
    }

    /**
     * Get the full thumbnail URL
     */
    public function getThumbnailFullUrlAttribute()
    {
        if ($this->thumbnail_url) {
            return $this->thumbnail_url;
        }
        
        if ($this->thumbnail) {
            if (str_starts_with($this->thumbnail, 'http')) {
                return $this->thumbnail;
            }
            return asset('storage/' . $this->thumbnail);
        }
        
        return null;
    }

    /**
     * Get the full video URL
     */
    public function getVideoFullUrlAttribute()
    {
        if ($this->video_url) {
            return $this->video_url;
        }
        
        if ($this->video_path) {
            if (str_starts_with($this->video_path, 'http')) {
                return $this->video_path;
            }
            return asset('storage/' . $this->video_path);
        }
        
        return null;
    }
}
