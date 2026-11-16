<?php

namespace App\Http\Controllers;

use App\Models\PurchaseAuditLog;
use App\Models\PurchaseIntent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class PurchaseIntentController extends Controller
{
    public function index(Request $request)
    {
        $intents = PurchaseIntent::where('user_id', $request->user()->id)
            ->latest()
            ->paginate(10);

        return response()->json([
            'data' => $intents->items(),
            'meta' => [
                'current_page' => $intents->currentPage(),
                'last_page' => $intents->lastPage(),
                'per_page' => $intents->perPage(),
                'total' => $intents->total(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_url' => ['required', 'string', 'max:2048'],
            'title' => ['sometimes', 'string', 'max:255'],
            'target_price' => ['sometimes', 'numeric', 'min:0'],
            'currency' => ['sometimes', 'string', 'max:10'],
            'seller' => ['sometimes', 'string', 'max:255'],
            'notes' => ['sometimes', 'string'],
            'watch_id' => ['sometimes', 'integer'],
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }

        $intent = PurchaseIntent::create(array_merge(
            $validator->validated(),
            ['user_id' => $request->user()->id, 'status' => 'draft']
        ));

        $this->audit($intent, $request->user()->id, 'created', null, 'draft', $intent->toArray());

        return response()->json(['message' => 'Purchase intent created', 'data' => $intent], 201);
    }

    public function show(Request $request, int $id)
    {
        $intent = PurchaseIntent::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        return response()->json(['data' => $intent]);
    }

    public function update(Request $request, int $id)
    {
        $intent = PurchaseIntent::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $oldStatus = $intent->status;

        $validator = Validator::make($request->all(), [
            'product_url' => ['sometimes', 'string', 'max:2048'],
            'title' => ['sometimes', 'string', 'max:255'],
            'target_price' => ['sometimes', 'numeric', 'min:0'],
            'currency' => ['sometimes', 'string', 'max:10'],
            'seller' => ['sometimes', 'string', 'max:255'],
            'notes' => ['sometimes', 'string'],
            'watch_id' => ['sometimes', 'integer'],
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }

        $intent->update($validator->validated());

        $this->audit($intent, $request->user()->id, 'updated', $oldStatus, $intent->status, $validator->validated());

        return response()->json(['message' => 'Purchase intent updated', 'data' => $intent]);
    }

    public function destroy(Request $request, int $id)
    {
        $intent = PurchaseIntent::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $intent->delete();

        return response()->json(null, 204);
    }

    /**
     * Transition a purchase intent to awaiting_approval.
     */
    public function submit(Request $request, int $id)
    {
        $intent = PurchaseIntent::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        if (! $intent->canTransitionTo('awaiting_approval')) {
            throw ValidationException::withMessages([
                'status' => ['Cannot transition to awaiting_approval from '.$intent->status],
            ]);
        }

        $oldStatus = $intent->status;
        $intent->update(['status' => 'awaiting_approval']);
        $this->audit($intent, $request->user()->id, 'submitted', $oldStatus, 'awaiting_approval', null);

        return response()->json(['message' => 'Purchase intent submitted for approval', 'data' => $intent]);
    }

    protected function audit(
        PurchaseIntent $intent,
        ?int $userId,
        string $action,
        ?string $oldStatus,
        ?string $newStatus,
        ?array $data
    ): void {
        PurchaseAuditLog::create([
            'auditable_type' => PurchaseIntent::class,
            'auditable_id' => $intent->id,
            'user_id' => $userId,
            'action' => $action,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'data' => $data,
        ]);
    }
}
