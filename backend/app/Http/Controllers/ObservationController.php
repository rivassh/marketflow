<?php

namespace App\Http\Controllers;

use App\Models\Watch;
use Illuminate\Http\Request;

class ObservationController extends Controller
{
    public function index(Request $request, int $id)
    {
        $watch = Watch::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $perPage = $request->get('per_page', 10);
        $observations = $watch->observations()
            ->orderBy('observed_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'data' => $observations->items(),
            'meta' => [
                'current_page' => $observations->currentPage(),
                'last_page' => $observations->lastPage(),
                'per_page' => $observations->perPage(),
                'total' => $observations->total(),
            ],
        ]);
    }
}
