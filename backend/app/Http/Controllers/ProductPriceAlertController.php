<?php

namespace App\Http\Controllers;

use App\Models\ProductPriceAlert;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ProductPriceAlertController extends Controller
{
    /**
     * Display a listing of the resource with search, filter, and sort.
     */
    public function index(Request $request)
    {
        $query = ProductPriceAlert::with(['product']);
        
        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('provider', 'like', "%{$search}%");
            });
        }
        
        // Filter by provider
        if ($request->filled('filter') && $request->filter === 'provider') {
            $query->whereHas('product', function ($q) use ($request) {
                $q->where('provider', 'like', "%{$request->provider}%");
            });
        }
        
        // Filter by availability
        if ($request->filled('filter') && $request->filter === 'availability') {
            $query->whereHas('product', function ($q) use ($request) {
                $q->where('last_known_status', $request->availability);
            });
        }
        
        // Filter by stock status
        if ($request->filled('filter') && $request->filter === 'stock_status') {
            $query->whereHas('product', function ($q) use ($request) {
                $q->where('metadata->stock_status', $request->stock_status);
            });
        }
        
        // Sort functionality
        if ($request->filled('sort')) {
            $sortColumn = $request->sort;
            $sortDirection = $request->direction ?? 'asc';
            
            switch ($sortColumn) {
                case 'product.title':
                    $query->join('products', 'product_price_alerts.product_id', '=', 'products.id')
                          ->select('product_price_alerts.*')
                          ->orderBy('products.title', $sortDirection);
                    break;
                case 'min_price':
                    $query->orderBy('min_price', $sortDirection);
                    break;
                case 'max_price':
                    $query->orderBy('max_price', $sortDirection);
                    break;
                case 'is_active':
                    $query->orderBy('is_active', $sortDirection);
                    break;
            }
        } else {
            $query->latest();
        }
        
        // Pagination
        $perPage = $request->get('perPage', 10);
        $alerts = $query->paginate($perPage);
        
        return response()->json([
            'data' => $alerts->items(),
            'meta' => [
                'current_page' => $alerts->currentPage(),
                'last_page' => $alerts->lastPage(),
                'per_page' => $alerts->perPage(),
                'total' => $alerts->total(),
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => ['required', 'exists:products,id'],
            'min_price' => ['required', 'integer', 'min:0'],
            'max_price' => ['required', 'integer', 'min:0'],
            'currency' => ['required', 'string', 'max:3', 'in:IRR,USD,EUR'],
            'availability' => ['required', 'in:available,unavailable,unknown'],
            'stock_status' => ['nullable', 'string', 'max:255'],
            'seller_name' => ['nullable', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors());
        }

        if ($request->min_price >= $request->max_price) {
            throw new \Exception('min_price must be less than max_price');
        }

        $alert = ProductPriceAlert::create([
            'product_id' => $request->product_id,
            'min_price' => $request->min_price,
            'max_price' => $request->max_price,
            'currency' => $request->currency,
            'availability' => $request->availability,
            'stock_status' => $request->stock_status,
            'seller_name' => $request->seller_name,
            'is_active' => $request->is_active,
        ]);

        return response()->json([
            'message' => 'Price alert created successfully',
            'alert' => $alert
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request)
    {
        $alert = ProductPriceAlert::with('product')->findOrFail($request->id);
        
        return response()->json([
            'alert' => $alert
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        $alert = ProductPriceAlert::findOrFail($request->id);
        
        $validator = Validator::make($request->all(), [
            'product_id' => ['sometimes', 'exists:products,id'],
            'min_price' => ['nullable', 'integer', 'min:0'],
            'max_price' => ['nullable', 'integer', 'min:0'],
            'currency' => ['nullable', 'string', 'max:3', 'in:IRR,USD,EUR'],
            'availability' => ['nullable', 'in:available,unavailable,unknown'],
            'stock_status' => ['nullable', 'string', 'max:255'],
            'seller_name' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        if ($request->min_price && $request->max_price) {
            if ($request->min_price >= $request->max_price) {
                return response()->json(['error' => 'min_price must be less than max_price'], 400);
            }
        }

        $alert->update($validator->validated());
        
        return response()->json([
            'message' => 'Price alert updated successfully',
            'alert' => $alert
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request)
    {
        $alert = ProductPriceAlert::findOrFail($request->id);
        $alert->delete();

        return response()->json([
            'message' => 'Price alert deleted successfully'
        ], 204);
    }
}