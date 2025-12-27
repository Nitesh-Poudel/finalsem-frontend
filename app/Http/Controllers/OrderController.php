<?php

namespace App\Http\Controllers;

use App\Http\Resources\OrderResource;
use App\Models\Offer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\DeliveryController;

class OrderController extends Controller
{
    protected $deliveryController;

    public function __construct(DeliveryController $deliveryController)
    {
        $this->deliveryController = $deliveryController;
    }



    public function index()
    {
        return Order::with(['orderItems.product'])->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'table_number' => 'nullable|integer|min:1',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'order_type' => 'required',
            'delivery_info' => ''
        ]);

        DB::beginTransaction();


        try {
            $totalAmount = 0;

            foreach ($validated['items'] as $item) {
                $product = Product::find($item['product_id']);
                if ($product->stock < $item['quantity']) {
                   return response()->json([
                        'success' => false,
                        'message' => "{$product->name} is out of stock for the requested quantity."
                    ], 400);
                }
            }

            $order = Order::where('table_number', $validated['table_number'])
                ->where('status', '!=', 'completed')
                ->first();


            if (!$order) {
                $order = Order::create([
                    'table_number' => $validated['table_number'],
                    'status' => 'queued',
                    'total_amount' => 0,
                    'user_id' => Auth::id()??null,
                    'order_type' => $validated['order_type'],
                ]);
            }

            if ($request->order_type === 'remote-delivery') {
                // $validationRules['delivery_info'] = 'required|array';
                // $validationRules['delivery_info.use_current_location'] = 'required|boolean';
                
                // if ($request->input('delivery_info.use_current_location')) {
                //     // GPS location validation
                //     $validationRules['delivery_info.latitude'] = 'required|numeric|between:-90,90';
                //     $validationRules['delivery_info.longitude'] = 'required|numeric|between:-180,180';
                //     $validationRules['delivery_info.accuracy'] = 'nullable|numeric|min:0';
                // } else {
                //     // Manual address validation
                //     $validationRules['delivery_info.ward_number'] = 'required|string|max:20';
                //     $validationRules['delivery_info.tole'] = 'required|string|max:100';
                //     $validationRules['delivery_info.city'] = 'required|string|in:Kathmandu';
                //     $validationRules['delivery_info.district'] = 'required|string|in:Kathmandu';
                //     $validationRules['delivery_info.province'] = 'required|string|in:Bagmati';
                //     $validationRules['delivery_info.phone'] = [
                //         'required',
                //         'string',
                //         'regex:/^(98|97|96)[0-9]{8}$/'
                //     ];
                //     $validationRules['delivery_info.landmark'] = 'nullable|string|max:500';
                // }

                    // In your store method, after creating the order:
                    // if ($validated['order_type'] === 'remote-delivery') {
                        // Create instance directly
                        $deliveryController = new DeliveryController();
                        
                        // Call the method
                        $delivery = $deliveryController->createDeliveryForOrder(
                            $order, 
                            $validated['delivery_info']
                        );
                    // }
            }


            foreach ($validated['items'] as $item) {
                $product = Product::find($item['product_id']);
                $originalPrice = $product->price * $item['quantity'];
                $discountedPrice = 0;
                $freebies = 0;


                $offer = Offer::where('type', 'product')
                    ->where('product_id', $product->id)
                    ->where('is_active', true)
                    ->where('start_date', '<=', now())
                    ->where('end_date', '>=', now())
                    ->first();



                if ($offer) {
                    switch ($offer->offer_kind) {
                        case 'percentage':
                            $discountedPrice += ($product->price * ($offer->value / 100)) * $item['quantity'];
                            break;

                        case 'fixed_amount':
                            $discountedPrice += ($offer->value) * $item['quantity'];
                            if ($discountedPrice < 0) $discountedPrice = 0;
                            break;

                        case 'buy_x_get_y':
                            if ($item['quantity'] >= $offer->buy_quantity) {
                                $freebies = $offer->get_quantity;
                            }
                            break;
                    }

                    $offer->num_used += $item['quantity'];
                    if ($offer->num_used >= $offer->max_usage) {
                        $offer->is_active = false;
                    }
                    $offer->save();
                }


                $orderItem = OrderItem::where('order_id', $order->id)
                    ->where('product_id', $product->id)
                    ->first();


                if ($orderItem) {
                    $orderItem->quantity += $item['quantity'];
                    $orderItem->total_product_price += $originalPrice;
                    $orderItem->discount += $discountedPrice;
                    $orderItem->grand_total += $originalPrice - $discountedPrice;
                    $orderItem->save();
                } else {
                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $product->id,
                        'quantity' => $item['quantity'],
                        'unit_price' => $product->price,
                        'total_product_price' => $originalPrice,
                        'discount' => $discountedPrice,
                        'grand_total' => $originalPrice - $discountedPrice,
                        'offer_id' => $offer?->id,
                        
                    ]);
                }


                $product->decrement('stock', $item['quantity']);
                if ($freebies) {
                    $product->decrement('stock', $freebies);
                }

                $totalAmount += $originalPrice - $discountedPrice;
            }


            $globalOffer = Offer::where('type', 'global')
                ->where('is_active', true)
                ->where('start_date', '<=', now())
                ->where('end_date', '>=', now())
                ->first();

            $globalDiscount = 0;
            if ($globalOffer) {
                if ($globalOffer->offer_kind === 'percentage') {
                    $globalDiscount = $totalAmount * ($globalOffer->value / 100);
                } elseif ($globalOffer->offer_kind === 'fixed_amount') {
                    $globalDiscount = $globalOffer->value;
                }

                $totalAmount = max($totalAmount - $globalDiscount, 0);

                $globalOffer->num_used++;
                if ($globalOffer->num_used >= $globalOffer->max_usage) {
                    $globalOffer->is_active = false;
                }
                $globalOffer->save();
            }

            $order->total_amount += $totalAmount;
            $order->save();

            DB::commit();

            return response()->json([
                'message' => 'Order created successfully',
                'order_id' => $order->id,
                'order_status' => $order->status,
                'ordered_product' => $order->orderItems,
                'total_amount' => $order->total_amount,
                'global_discount' => $globalDiscount,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        if(!$id){
            $id = Auth::id();
        }
        $order = Order::with([
            'orderItems.product:id,name,price,stock,category_id'
        ])->find($id);

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        return response()->json([
            'order_id' => $order->id,
            'table_number' => $order->table_number,
            'status' => $order->status,
            'total_amount' => $order->total_amount,
            'created_at' => $order->created_at->format('Y/m/d H:i'),
            'order_items' => $order->orderItems->map(function ($item) {
                return [
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->name,
                    'unit_price' => $item->unit_price,
                    'quantity' => $item->quantity,
                    'total_product_price' => $item->total_product_price,
                    'discount' => $item->discount,
                    'grand_total' => $item->grand_total
                ];
            }),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //

    }

    public function orderStats(string $id)
    {
        $order = Order::with('orderItems.product')->findOrFail($id);

        $totalQuantity = $order->orderItems->sum('quantity');
        $totalProducts = $order->orderItems->count();
        $totalRevenue = $order->orderItems->sum('grand_total');
        $totalDiscount = $order->orderItems->sum('discount');
        $averageItemPrice = $totalQuantity ? $totalRevenue / $totalQuantity : 0;

        return response()->json([
            'order_id' => $order->id,
            'table_number' => $order->table_number,
            'status' => $order->status,
            'total_products_ordered' => $totalProducts,
            'total_quantity' => $totalQuantity,
            'total_revenue' => $totalRevenue,
            'total_discount' => $totalDiscount,
            'average_item_price' => $averageItemPrice,
            'created_at' => $order->created_at->format('Y/m/d H:i'),
            'items' => $order->orderItems->map(function ($item) {
                return [
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->name,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'total_product_price' => $item->total_product_price,
                    'discount' => $item->discount,
                    'grand_total' => $item->grand_total,
                    'offer_id' => $item->offer_id,
                ];
            })
        ]);
    }

    // -------------------------------
    // Overall statistics for all orders or filtered
    // -------------------------------
    public function orderStatisticsOverall(Request $request)
    {
        $query = Order::query();

        // Optional date filter
        if ($request->has('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->has('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $orders = $query->with('orderItems')->get();

        $totalOrders = $orders->count();
        $totalProducts = $orders->sum(fn($order) => $order->orderItems->count());
        $totalQuantity = $orders->sum(fn($order) => $order->orderItems->sum('quantity'));
        $totalRevenue = $orders->sum(fn($order) => $order->orderItems->sum('grand_total'));
        $totalDiscount = $orders->sum(fn($order) => $order->orderItems->sum('discount'));
        $averageOrderValue = $totalOrders ? $totalRevenue / $totalOrders : 0;
        $averageItemPrice = $totalQuantity ? $totalRevenue / $totalQuantity : 0;

        $productStats = OrderItem::select('product_id', DB::raw('SUM(quantity) as total_sold'))
            ->groupBy('product_id')
            ->orderByDesc('total_sold')
            ->first();

        // Orders count per status
        $statusCounts = $orders->groupBy('status')->map(fn($items) => $items->count());

        return response()->json([
            'total_orders' => $totalOrders,
            'total_products_ordered' => $totalProducts,
            'total_quantity_sold' => $totalQuantity,
            'total_revenue' => $totalRevenue,
            'total_discount' => $totalDiscount,
            'average_order_value' => $averageOrderValue,
            'average_item_price' => $averageItemPrice,
            'most_ordered_product_id' => $productStats->product_id ?? null,
            'most_ordered_quantity' => $productStats->total_sold ?? 0,
            'orders_status_breakdown' => $statusCounts, // e.g., ['queued' => 5, 'completed' => 10]
        ]);
    }

    public function monthlyOrderStats()
    {
        $orders = Order::select(
            DB::raw("MONTH(created_at) as month_number"),
            DB::raw("MONTHNAME(created_at) as month"),
            DB::raw("COUNT(id) as orders"),
            DB::raw("SUM(total_amount) as revenue")
        )
            ->groupBy(DB::raw("MONTH(created_at)"), DB::raw("MONTHNAME(created_at)"))
            ->orderBy(DB::raw("MONTH(created_at)"))
            ->get();

        $data = $orders->map(function ($order) {
            return [
                'month' => $order->month,
                'orders' => (int) $order->orders,
                'revenue' => (float) $order->revenue,
            ];
        });

        return response()->json(['data' => $data]);
    }

    public function getOrderByStatus(Request $request, $status = null)
    {
        $query = Order::with('orderItems.product');

        if ($status) {
            $query->where('status', $status);
        }

        $orders = $query->get();

        return response()->json($orders);
    }


    public function updateOrderStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|string|in:queued,processing,ready,completed,cancelled',
        ]);

        $order = Order::find($id);
        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        $order->status = $validated['status'];
        $order->save();

        return response()->json(['message' => 'Order status updated successfully', 'order' => $order]);
    }

   public function getOrderHistory()
    {
        $user = Auth::user(); 
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not logged in'
            ], 401);
        }

        $orders = Order::with([
            'orderItems.product',
            'user'             
        ])
        ->where('user_id', $user->id)
        ->where('status','completed')
        ->orderBy('created_at', 'desc')
        ->get();

        return response()->json([
            'success' => true,
                'count'=>$orders->count(),
               'orders' => OrderResource::collection($orders)
        ]);
    }

public function getOrderTrack()
{
    $user = Auth::user(); 
   
    if (!$user) {
        return response()->json([
            'success' => false,
            'message' => 'User not logged in'
        ], 401);
    }

    // Get the last order of the logged-in user
    $order = Order::with([
        'orderItems.product:id,name,price,stock,category_id'
    ])
    ->where('user_id', $user->id)
    ->latest() // order by created_at desc
    ->first();

    if (!$order) {
        return response()->json(['message' => 'Order not found'], 404);
    }

    return response()->json([
        'order_id' => $order->id,
        'table_number' => $order->table_number,
        'status' => $order->status,
        'total_amount' => $order->total_amount,
        'created_at' => $order->created_at->format('Y/m/d H:i'),
        'order_items' => $order->orderItems->map(function ($item) {
            return [
                'product_id' => $item->product_id,
                'product_name' => $item->product->name,
                'unit_price' => $item->unit_price,
                'quantity' => $item->quantity,
                'total_product_price' => $item->total_product_price,
                'discount' => $item->discount,
                'grand_total' => $item->grand_total
            ];
        }),
    ]);
}

}
