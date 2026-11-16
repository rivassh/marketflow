<?php

namespace App\Http\Controllers;

use App\Models\Watch;
use App\Providers\Processors\TorobProcessor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class WatchController extends Controller
{
    public function index(Request $request)
    {
        $watches = Watch::where('user_id', $request->user()->id)
            ->withCount('observations')
            ->latest()
            ->paginate(10);

        return response()->json([
            'data' => $watches->items(),
            'meta' => [
                'current_page' => $watches->currentPage(),
                'last_page' => $watches->lastPage(),
                'per_page' => $watches->perPage(),
                'total' => $watches->total(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_url' => ['required', 'string', 'max:2048'],
            'workflow_type' => ['required', 'string', 'in:PRICE_RANGE_ALERT,PRICE_BELOW_ALERT,PRICE_ABOVE_ALERT,AVAILABILITY_ALERT,STOCK_STATUS_ALERT,SELLER_ALERT,JSON_FIELD_ALERT,HTML_SELECTOR_ALERT,COMPOSITE_ALERT'],
            'conditions' => ['required', 'array'],
            'frequency_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }

        $processor = new TorobProcessor;
        if (! $processor->supports($request->product_url)) {
            throw ValidationException::withMessages([
                'product_url' => ['The product URL is not supported. Please provide a valid torob.com URL.'],
            ]);
        }

        $watch = Watch::create([
            'user_id' => $request->user()->id,
            'product_url' => $request->product_url,
            'workflow_type' => $request->workflow_type,
            'conditions' => $request->conditions,
            'frequency_minutes' => $request->frequency_minutes,
            'is_active' => true,
            'is_tracked' => true,
        ]);

        return response()->json([
            'message' => 'Watch created successfully',
            'data' => $watch,
        ], 201);
    }

    public function show(Request $request, int $id)
    {
        $watch = Watch::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        return response()->json(['data' => $watch]);
    }

    public function update(Request $request, int $id)
    {
        $watch = Watch::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $validator = Validator::make($request->all(), [
            'product_url' => ['sometimes', 'string', 'max:2048'],
            'workflow_type' => ['sometimes', 'string'],
            'conditions' => ['sometimes', 'array'],
            'frequency_minutes' => ['sometimes', 'integer', 'min:1', 'max:1440'],
            'is_active' => ['sometimes', 'boolean'],
            'is_tracked' => ['sometimes', 'boolean'],
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }

        $watch->update($validator->validated());

        return response()->json([
            'message' => 'Watch updated successfully',
            'data' => $watch,
        ]);
    }

    public function destroy(Request $request, int $id)
    {
        $watch = Watch::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $watch->delete();

        return response()->json(null, 204);
    }
}
