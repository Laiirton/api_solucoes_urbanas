<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceRequest extends Model
{
    use HasFactory;

    protected $table = 'service_requests';

    protected $fillable = [
        'user_id',
        'service_id',
        'protocol_number',
        'service_title',
        'category',
        'request_data',
        'status',
        'attachments'
    ];

    protected $casts = [
        'request_data' => 'json',
        'attachments' => 'json',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => 'pending',
    ];

    // Define a ordem dos campos no JSON
    protected $appends = [];
    
    public function toArray()
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'service_id' => $this->service_id,
            'protocol_number' => $this->protocol_number,
            'service_title' => $this->service_title,
            'category' => $this->category,
            'request_data' => $this->request_data,
            'attachments' => $this->attachments,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function isInProgress()
    {
        return $this->status === 'in_progress';
    }

    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    public function isCancelled()
    {
        return $this->status === 'cancelled';
    }

    public static function generateProtocolNumber()
    {
        do {
            $letters = '';
            for ($i = 0; $i < 4; $i++) {
                $letters .= chr(rand(65, 90));
            }
            
            $numbers = '';
            for ($i = 0; $i < 8; $i++) {
                $numbers .= rand(0, 9);
            }
            
            $protocol = $letters . $numbers;
        } while (self::where('protocol_number', $protocol)->exists());
        
        return $protocol;
    }
}