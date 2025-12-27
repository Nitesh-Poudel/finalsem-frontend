<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Delivery extends Model
{
    use HasFactory;

    /**
     * Using guarded instead of fillable
     * Empty array means no fields are guarded (all fields are fillable)
     */
    protected $guarded = [];

    protected $casts = [
        'is_current_location' => 'boolean',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'accuracy' => 'decimal:2',
        'delivery_fee' => 'decimal:2',
        'estimated_delivery_time' => 'datetime',
        'actual_delivery_time' => 'datetime'
    ];

    // Relationships
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeInKathmandu($query)
    {
        return $query->where('district', 'Kathmandu');
    }

    public function scopeUsesGps($query)
    {
        return $query->where('is_current_location', true);
    }

    public function scopeUsesAddress($query)
    {
        return $query->where('is_current_location', false);
    }

    // Helper Methods
    public function getFullAddressAttribute()
    {
        if ($this->is_current_location) {
            return "GPS Location: {$this->latitude}, {$this->longitude}";
        }

        $parts = [];
        if ($this->ward_number) $parts[] = "Ward {$this->ward_number}";
        if ($this->tole) $parts[] = $this->tole;
        if ($this->landmark) $parts[] = "Near {$this->landmark}";
        if ($this->local_government) $parts[] = $this->local_government;
        if ($this->municipality) $parts[] = $this->municipality;
        if ($this->district) $parts[] = $this->district;
        if ($this->province) $parts[] = $this->province;
        
        return implode(', ', $parts);
    }

    public function getAddressTypeAttribute()
    {
        return $this->is_current_location ? 'GPS Location' : 'Manual Address';
    }

    public function generateTrackingCode()
    {
        $this->tracking_code = 'DEL' . strtoupper(substr(md5($this->order_id . time()), 0, 10));
        return $this->tracking_code;
    }

    public function getCoordinatesAttribute()
    {
        if ($this->latitude && $this->longitude) {
            return [
                'latitude' => (float) $this->latitude,
                'longitude' => (float) $this->longitude,
                'accuracy' => $this->accuracy ? (float) $this->accuracy : null
            ];
        }
        return null;
    }

    public function markAsDelivered($notes = null)
    {
        $this->status = 'delivered';
        $this->actual_delivery_time = now();
        if ($notes) {
            $this->delivery_notes = $notes;
        }
        $this->save();
        
        return $this;
    }

    public function isDelivered()
    {
        return $this->status === 'delivered';
    }

    public function getDeliveryFeeFormattedAttribute()
    {
        return 'Rs ' . number_format($this->delivery_fee, 2);
    }
}