<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\product_warehouse;
use App\Models\RestockRequest;
use App\Models\Transfer;
use App\Models\TransferDetail;
use App\Models\Unit;
use App\Models\Warehouse;
use App\Models\UserWarehouse;
use App\utils\TelegramService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RestockController extends BaseController
{
    private function getAccessibleWarehouseIds($user)
    {
        if ($user->is_all_warehouses) {
            return Warehouse::where('deleted_at', '=', null)->pluck('id')->toArray();
        }

        return UserWarehouse::where('user_id', $user->id)->pluck('warehouse_id')->toArray();
    }

    private function getTransferRef()
    {
        $last = \DB::table('transfers')->latest('id')->first();
        if ($last) {
            $item = $last->Ref;
            $nwMsg = explode("_", $item);
            $inMsg = $nwMsg[1] + 1;
            return $nwMsg[0] . '_' . $inMsg;
        }
        return 'TR_1111';
    }

    private function buildTransferRef()
    {
        return $this->getTransferRef();
    }

    public function applyTransferSent(RestockRequest $restockRequest, $approvedByUserId = null)
    {
        if ($restockRequest->status !== 'pending') {
            return ['success' => false, 'message' => 'Request already processed'];
        }

        $items = $restockRequest->items;
        if (!is_array($items) || count($items) === 0) {
            return ['success' => false, 'message' => 'No items to transfer'];
        }

        $grandTotal = 0;
        $itemsCount = 0;

        \DB::transaction(function () use ($restockRequest, $items, $approvedByUserId, &$grandTotal, &$itemsCount) {
            $order = new Transfer();
            $order->date = $restockRequest->date ?: Carbon::now()->format('Y-m-d');
            $order->Ref = $this->buildTransferRef();
            $order->from_warehouse_id = $restockRequest->from_warehouse_id;
            $order->to_warehouse_id = $restockRequest->to_warehouse_id;
            $order->items = count($items);
            $order->tax_rate = 0;
            $order->TaxNet = 0;
            $order->discount = 0;
            $order->shipping = 0;
            $order->statut = 'sent';
            $order->notes = $restockRequest->notes;
            $order->GrandTotal = 0;
            if ($approvedByUserId) {
                $order->user_id = $approvedByUserId;
            } elseif (Auth::check()) {
                $order->user_id = Auth::user()->id;
            } else {
                $order->user_id = $restockRequest->requested_by;
            }
            $order->save();

            foreach ($items as $value) {
                $product = Product::with('unitPurchase')->where('id', $value['product_id'])->firstOrFail();

                $purchaseUnitId = $value['purchase_unit_id'] ?? $product['unitPurchase']->id;
                $unit = Unit::where('id', $purchaseUnitId)->first();
                if (!$unit) {
                    throw new \Exception('Invalid purchase unit');
                }

                $productVariantId = $value['product_variant_id'] ?? null;

                $productWarehouseQuery = product_warehouse::where('deleted_at', '=', null)
                    ->where('warehouse_id', $restockRequest->from_warehouse_id)
                    ->where('product_id', $value['product_id']);

                if ($productVariantId !== null) {
                    $productWarehouseQuery->where('product_variant_id', $productVariantId);
                } else {
                    $productWarehouseQuery->where('product_variant_id', '=', null);
                }

                $product_warehouse_from = $productWarehouseQuery->first();
                if (!$product_warehouse_from) {
                    throw new \Exception('Product not found in source warehouse');
                }

                if ($unit->operator == '/') {
                    $delta = $value['quantity'] / $unit->operator_value;
                } else {
                    $delta = $value['quantity'] * $unit->operator_value;
                }

                if ($product_warehouse_from->qte < $delta) {
                    throw new \Exception('Insufficient stock for product_id ' . $value['product_id']);
                }

                $product_warehouse_from->qte -= $delta;
                $product_warehouse_from->save();

                $itemsCount += 1;

                $unitCost = $product->cost;
                if ($product['unitPurchase']->operator == '/') {
                    $unitCost = $product->cost / $product['unitPurchase']->operator_value;
                } else {
                    $unitCost = $product->cost * $product['unitPurchase']->operator_value;
                }

                $subtotal = $unitCost * $value['quantity'];
                $grandTotal += $subtotal;

                TransferDetail::insert([
                    'transfer_id' => $order->id,
                    'quantity' => $value['quantity'],
                    'purchase_unit_id' => $purchaseUnitId,
                    'product_id' => $value['product_id'],
                    'product_variant_id' => $productVariantId,
                    'cost' => $unitCost,
                    'TaxNet' => 0,
                    'tax_method' => $product->tax_method,
                    'discount' => 0,
                    'discount_method' => '2',
                    'total' => $subtotal,
                ]);
            }

            $order->items = $itemsCount;
            $order->GrandTotal = $grandTotal;
            $order->save();

            $restockRequest->status = 'sent';
            $restockRequest->approved_by = $approvedByUserId;
            $restockRequest->transfer_id = $order->id;
            $restockRequest->telegram_token = null;
            $restockRequest->save();
        }, 10);

        return ['success' => true];
    }

    private function completeTransfer(RestockRequest $restockRequest, $completedByUserId = null)
    {
        if ($restockRequest->status !== 'sent' || !$restockRequest->transfer_id) {
            return ['success' => false, 'message' => 'Request not ready for completion'];
        }

        $transfer = Transfer::find($restockRequest->transfer_id);
        if (!$transfer) {
            return ['success' => false, 'message' => 'Transfer not found'];
        }

        $details = TransferDetail::where('transfer_id', $transfer->id)->get();
        if ($details->isEmpty()) {
            return ['success' => false, 'message' => 'Transfer has no details'];
        }

        \DB::transaction(function () use ($restockRequest, $transfer, $details, $completedByUserId) {
            foreach ($details as $detail) {
                $unit = null;
                if ($detail->purchase_unit_id) {
                    $unit = Unit::where('id', $detail->purchase_unit_id)->first();
                } else {
                    $product_unit_purchase_id = Product::with('unitPurchase')
                        ->where('id', $detail->product_id)
                        ->first();
                    if ($product_unit_purchase_id && $product_unit_purchase_id['unitPurchase']) {
                        $unit = Unit::where('id', $product_unit_purchase_id['unitPurchase']->id)->first();
                    }
                }

                if (!$unit) {
                    throw new \Exception('Invalid purchase unit');
                }

                if ($unit->operator == '/') {
                    $delta = $detail->quantity / $unit->operator_value;
                } else {
                    $delta = $detail->quantity * $unit->operator_value;
                }

                $productWarehouseToQuery = product_warehouse::where('deleted_at', '=', null)
                    ->where('warehouse_id', $transfer->to_warehouse_id)
                    ->where('product_id', $detail->product_id);

                if ($detail->product_variant_id !== null) {
                    $productWarehouseToQuery->where('product_variant_id', $detail->product_variant_id);
                } else {
                    $productWarehouseToQuery->where('product_variant_id', '=', null);
                }

                $product_warehouse_to = $productWarehouseToQuery->first();
                if ($product_warehouse_to) {
                    $product_warehouse_to->qte += $delta;
                    $product_warehouse_to->save();
                }
            }

            $transfer->statut = 'completed';
            if ($completedByUserId) {
                $transfer->user_id = $completedByUserId;
            }
            $transfer->save();

            $restockRequest->status = 'completed';
            $restockRequest->approved_by = $completedByUserId;
            $restockRequest->save();
        }, 10);

        return ['success' => true];
    }

    public function notifyRequesterStatus(RestockRequest $restockRequest, $status)
    {
        if (!$restockRequest->requested_by) {
            return;
        }

        $requester = \App\Models\User::find($restockRequest->requested_by);
        if (!$requester || !$requester->telegram_id) {
            return;
        }

        $fromWarehouse = Warehouse::find($restockRequest->from_warehouse_id);
        $toWarehouse = Warehouse::find($restockRequest->to_warehouse_id);

        $lines = [];
        $items = is_array($restockRequest->items) ? $restockRequest->items : [];
        foreach ($items as $item) {
            $product = Product::find($item['product_id'] ?? null);
            $line = ($product ? $product->name : 'Product') . ' x ' . ($item['quantity'] ?? 0);
            $lines[] = $line;
        }

        $message = "📦 *Restock Request Update*\n"
            . "Status: " . strtoupper($status) . "\n"
            . "From: " . ($fromWarehouse ? $fromWarehouse->name : $restockRequest->from_warehouse_id) . "\n"
            . "To: " . ($toWarehouse ? $toWarehouse->name : $restockRequest->to_warehouse_id) . "\n"
            . "Items:\n- " . implode("\n- ", $lines);

        $telegram = new TelegramService();
        $telegram->sendMessage([
            'chat_id' => $requester->telegram_id,
            'text' => $message,
            'parse_mode' => 'Markdown',
        ]);
    }

    public function warehouses(Request $request)
    {
        $this->authorizeForUser($request->user('api'), 'create', Transfer::class);

        $user = auth()->user();
        $warehouseIds = $this->getAccessibleWarehouseIds($user);

        $warehouses = Warehouse::where('deleted_at', '=', null)
            ->whereIn('id', $warehouseIds)
            ->get(['id', 'name']);

        return response()->json(['warehouses' => $warehouses]);
    }

    public function products(Request $request, $id)
    {
        $this->authorizeForUser($request->user('api'), 'create', Transfer::class);

        $user = auth()->user();
        $warehouseIds = $this->getAccessibleWarehouseIds($user);
        if (!in_array((int) $id, $warehouseIds, true)) {
            return response()->json(['message' => 'Warehouse not accessible'], 403);
        }

        $stockOnly = $request->query('stock', '1') === '1';

        $productWarehouseQuery = product_warehouse::with('product.unitPurchase', 'product.unitSale', 'productVariant')
            ->where('warehouse_id', $id)
            ->where('deleted_at', '=', null);

        if ($stockOnly) {
            $productWarehouseQuery->where('qte', '>', 0);
        }

        $product_warehouse_data = $productWarehouseQuery->get();
        $data = [];

        foreach ($product_warehouse_data as $product_warehouse) {
            $product = $product_warehouse->product;

            if (!$product) {
                continue;
            }

            if ($product_warehouse->product_variant_id) {
                $item['product_variant_id'] = $product_warehouse->product_variant_id;
                $item['code'] = $product_warehouse['productVariant']->name . '-' . $product->code;
                $item['variant'] = $product_warehouse['productVariant']->name;
            } else {
                $item['product_variant_id'] = null;
                $item['variant'] = null;
                $item['code'] = $product->code;
            }

            $item['product_id'] = $product->id;
            $item['name'] = $product->name;
            $item['qte'] = $product_warehouse->qte;
            $item['unitPurchase'] = $product['unitPurchase']->ShortName;
            $item['purchase_unit_id'] = $product['unitPurchase']->id;
            $item['unitSale'] = $product['unitSale']->ShortName;
            $item['unit_sale_id'] = $product['unitSale']->id;

            if ($product['unitPurchase']->operator == '/') {
                $item['qte_purchase'] = round($product_warehouse->qte * $product['unitPurchase']->operator_value, 5);
            } else {
                $item['qte_purchase'] = round($product_warehouse->qte / $product['unitPurchase']->operator_value, 5);
            }

            if ($product['unitSale']->operator == '/') {
                $item['qte_sale'] = $product_warehouse->qte * $product['unitSale']->operator_value;
            } else {
                $item['qte_sale'] = $product_warehouse->qte / $product['unitSale']->operator_value;
            }

            $data[] = $item;
        }

        return response()->json(['products' => $data]);
    }

    public function store(Request $request)
    {
        $this->authorizeForUser($request->user('api'), 'create', Transfer::class);

        $request->validate([
            'from_warehouse_id' => 'required|integer',
            'to_warehouse_id' => 'required|integer|different:from_warehouse_id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer',
            'items.*.quantity' => 'required|numeric|min:0.00001',
        ]);

        $user = auth()->user();
        $warehouseIds = $this->getAccessibleWarehouseIds($user);

        if (!in_array((int) $request->from_warehouse_id, $warehouseIds, true)
            || !in_array((int) $request->to_warehouse_id, $warehouseIds, true)) {
            return response()->json(['message' => 'Warehouse not accessible'], 403);
        }

        $date = $request->input('date', Carbon::now()->format('Y-m-d'));
        $notes = $request->input('notes', '');

        $token = bin2hex(random_bytes(8));

        $restockRequest = RestockRequest::create([
            'from_warehouse_id' => $request->from_warehouse_id,
            'to_warehouse_id' => $request->to_warehouse_id,
            'requested_by' => Auth::user()->id,
            'status' => 'pending',
            'telegram_token' => $token,
            'date' => $date,
            'notes' => $notes,
            'items' => $request->items,
        ]);

        $fromWarehouse = Warehouse::find($request->from_warehouse_id);
        $toWarehouse = Warehouse::find($request->to_warehouse_id);

        $lines = [];
        foreach ($request->items as $item) {
            $product = Product::find($item['product_id']);
            $line = ($product ? $product->name : 'Product #' . $item['product_id']) . ' x ' . $item['quantity'];
            $lines[] = $line;
        }

        $message = "🧾 *Restock Request*\n"
            . "From: " . ($fromWarehouse ? $fromWarehouse->name : $request->from_warehouse_id) . "\n"
            . "To: " . ($toWarehouse ? $toWarehouse->name : $request->to_warehouse_id) . "\n"
            . "Date: " . $date . "\n"
            . "Items:\n- " . implode("\n- ", $lines);

        $adminChatId = config('services.telegram.admin_chat_id');
        if ($adminChatId) {
            $telegram = new TelegramService();
            $telegram->sendMessage([
                'chat_id' => $adminChatId,
                'text' => $message,
                'parse_mode' => 'Markdown',
                'reply_markup' => [
                    'inline_keyboard' => [
                        [
                            ['text' => 'Approve', 'callback_data' => "restock:approve:{$restockRequest->id}:{$token}"],
                            ['text' => 'Reject', 'callback_data' => "restock:reject:{$restockRequest->id}:{$token}"],
                        ],
                    ],
                ],
            ]);
        }

        return response()->json(['success' => true, 'request_id' => $restockRequest->id]);
    }

    public function approve(Request $request, $id)
    {
        $this->authorizeForUser($request->user('api'), 'create', Transfer::class);

        $restockRequest = RestockRequest::findOrFail($id);
        $result = $this->applyTransferSent($restockRequest, Auth::user()->id);

        if (!$result['success']) {
            return response()->json($result, 400);
        }

        $this->notifyRequesterStatus($restockRequest, 'approved');
        return response()->json(['success' => true]);
    }

    public function reject(Request $request, $id)
    {
        $this->authorizeForUser($request->user('api'), 'create', Transfer::class);

        $restockRequest = RestockRequest::findOrFail($id);
        if ($restockRequest->status !== 'pending') {
            return response()->json(['success' => false, 'message' => 'Request already processed'], 400);
        }

        $restockRequest->status = 'rejected';
        $restockRequest->approved_by = Auth::user()->id;
        $restockRequest->telegram_token = null;
        $restockRequest->save();

        $this->notifyRequesterStatus($restockRequest, 'rejected');
        return response()->json(['success' => true]);
    }

    public function complete(Request $request, $id)
    {
        $this->authorizeForUser($request->user('api'), 'create', Transfer::class);

        $restockRequest = RestockRequest::findOrFail($id);
        $user = auth()->user();

        $warehouseIds = $this->getAccessibleWarehouseIds($user);
        if (!in_array((int) $restockRequest->to_warehouse_id, $warehouseIds, true)) {
            return response()->json(['success' => false, 'message' => 'Destination warehouse not accessible'], 403);
        }

        $result = $this->completeTransfer($restockRequest, $user->id);
        if (!$result['success']) {
            return response()->json($result, 400);
        }

        $this->notifyRequesterStatus($restockRequest, 'completed');
        return response()->json(['success' => true]);
    }

    public function index(Request $request)
    {
        $this->authorizeForUser($request->user('api'), 'view', Transfer::class);

        $status = $request->query('status', 'pending');
        $requests = RestockRequest::where('status', $status)
            ->orderBy('id', 'desc')
            ->get();

        return response()->json(['requests' => $requests]);
    }
}
