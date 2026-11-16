<?php

namespace App\Http\Controllers;

use App\Models\PurchaseAuditLog;
use App\Models\PurchaseItem;
use App\Models\PurchaseOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class PurchaseOrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = PurchaseOrder::where('user_id', $request->user()->id)
            ->with('items')
            ->latest()
            ->paginate(10);

        return response()->json([
            'data' => $orders->items(),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'purchase_intent_id' => ['sometimes', 'integer', 'exists:purchase_intents,id'],
            'seller' => ['sometimes', 'string', 'max:255'],
            'total_amount' => ['sometimes', 'numeric', 'min:0'],
            'currency' => ['sometimes', 'string', 'max:10'],
            'notes' => ['sometimes', 'string'],
            'items' => ['sometimes', 'array'],
            'items.*.title' => ['required_with:items', 'string', 'max:255'],
            'items.*.product_url' => ['sometimes', 'string', 'max:2048'],
            'items.*.price' => ['sometimes', 'numeric', 'min:0'],
            'items.*.quantity' => ['sometimes', 'integer', 'min:1'],
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }

        $validated = $validator->validated();

        $order = PurchaseOrder::create([
            'user_id' => $request->user()->id,
            'purchase_intent_id' => $validated['purchase_intent_id'] ?? null,
            'seller' => $validated['seller'] ?? null,
            'total_amount' => $validated['total_amount'] ?? null,
            'currency' => $validated['currency'] ?? 'IRR',
            'notes' => $validated['notes'] ?? null,
            'status' => 'draft',
        ]);

        foreach ($validated['items'] ?? [] as $item) {
            PurchaseItem::create([
                'purchase_order_id' => $order->id,
                'title' => $item['title'],
                'product_url' => $item['product_url'] ?? null,
                'price' => $item['price'] ?? null,
                'quantity' => $item['quantity'] ?? 1,
                'currency' => $order->currency,
            ]);
        }

        $this->audit($order, $request->user()->id, 'created', null, 'draft', $order->toArray());

        return response()->json(['message' => 'Purchase order created', 'data' => $order->load('items')], 201);
    }

    public function show(Request $request, int $id)
    {
        $order = PurchaseOrder::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->with('items')
            ->firstOrFail();

        return response()->json(['data' => $order]);
    }

    public function submit(Request $request, int $id)
    {
        $order = PurchaseOrder::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        if (! $order->canTransitionTo('awaiting_approval')) {
            throw ValidationException::withMessages([
                'status' => ['Cannot submit from '.$order->status],
            ]);
        }

        $oldStatus = $order->status;
        $order->update(['status' => 'awaiting_approval']);
        $this->audit($order, $request->user()->id, 'submitted', $oldStatus, 'awaiting_approval', null);

        return response()->json(['message' => 'Purchase order submitted for approval', 'data' => $order]);
    }

    /**
     * Approve a purchase order. Only the owner can approve.
     * This is the explicit human-in-the-loop gate: the flow stops at
     * awaiting_approval until the user approves. No payment is processed.
     */
    public function approve(Request $request, int $id)
    {
        $order = PurchaseOrder::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        if (! $order->canTransitionTo('approved')) {
            throw ValidationException::withMessages([
                'status' => ['Cannot approve from '.$order->status],
            ]);
        }

        $oldStatus = $order->status;
        $order->update([
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by' => $request->user()->id,
        ]);
        $this->audit($order, $request->user()->id, 'approved', $oldStatus, 'approved', null);

        return response()->json(['message' => 'Purchase order approved', 'data' => $order]);
    }

    protected function audit(
        PurchaseOrder $order,
        ?int $userId,
        string $action,
        ?string $oldStatus,
        ?string $newStatus,
        ?array $data
    ): void {
        PurchaseAuditLog::create([
            'auditable_type' => PurchaseOrder::class,
            'auditable_id' => $order->id,
            'user_id' => $userId,
            'action' => $action,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'data' => $data,
        ]);
    }
}
