<?php

namespace App\Http\Controllers;

use App\Models\Delivery;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DeliveryController extends Controller
{
    /**
     * Create a delivery record for an order
     */
    public function createDeliveryForOrder(Order $order, array $deliveryData)
    {
        
        DB::transaction(function () use ($order, $deliveryData) {
            // Ensure delivery doesn't already exist for this order
            // if ($order->delivery()->exists()) {
            //     throw new \Exception('Delivery already exists for this order.');
            // }

            // Prepare the delivery data
            $deliveryData['order_id'] = $order->id;
            $deliveryData['status'] = 'pending';
            $deliveryData['delivery_fee'] = $deliveryData['delivery_fee'] ?? 100;
            $deliveryData['tracking_code'] = $this->generateTrackingCode($order->id);
            $deliveryData['estimated_delivery_time'] = now()->addMinutes(rand(45, 60));

            // Handle GPS location vs manual address
            if (isset($deliveryData['use_current_location']) && $deliveryData['use_current_location']) {
                // GPS location data
                $deliveryData['is_current_location'] = true;
                $deliveryData['latitude'] = $deliveryData['latitude'] ?? null;
                $deliveryData['longitude'] = $deliveryData['longitude'] ?? null;
                $deliveryData['accuracy'] = $deliveryData['accuracy'] ?? null;
                
                // Set default location to Kathmandu for GPS
                $deliveryData['district'] = 'Kathmandu';
                $deliveryData['province'] = 'Bagmati';
                
                // Remove the temporary use_current_location flag
                unset($deliveryData['use_current_location']);
            } else {
                // Manual address data
                $deliveryData['is_current_location'] = false;
                
                // Map the delivery_info fields to delivery table columns
                $deliveryData['ward_number'] = $deliveryData['ward_number'] ?? null;
                $deliveryData['tole'] = $deliveryData['tole'] ?? null;
                $deliveryData['district'] = $deliveryData['district'] ?? null;
                $deliveryData['province'] = $deliveryData['province'] ?? null;
                $deliveryData['landmark'] = $deliveryData['landmark'] ?? null;
                $deliveryData['phone'] = $deliveryData['phone'] ?? null;
                
                // Remove temporary flag if exists
                unset($deliveryData['use_current_location']);
            }

            // Create the delivery record
            $delivery = Delivery::create($deliveryData);

            return $delivery;
        });
    }

    /**
     * Generate unique tracking code
     */
    private function generateTrackingCode($orderId)
    {
        return 'DEL' . strtoupper(substr(md5($orderId . time() . rand(1000, 9999)), 0, 10));
    }

    /**
     * Update delivery status
     */
    public function updateDeliveryStatus(Delivery $delivery, $status, $notes = null)
    {
        $delivery->status = $status;
        
        if ($status === 'delivered') {
            $delivery->actual_delivery_time = now();
        }
        
        if ($notes) {
            $delivery->delivery_notes = $notes;
        }
        
        $delivery->save();
        
        return $delivery;
    }

    /**
     * Get delivery by tracking code
     */
    public function getDeliveryByTrackingCode($trackingCode)
    {
        return Delivery::where('tracking_code', $trackingCode)
            ->with('order.orderItems.product')
            ->firstOrFail();
    }

    /**
     * Get all pending deliveries
     */
    public function getPendingDeliveries()
    {
        return Delivery::where('status', 'pending')
            ->with('order.orderItems.product')
            ->get();
    }
}