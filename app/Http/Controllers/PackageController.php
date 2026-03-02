<?php

namespace App\Http\Controllers;

use App\Models\Package;
use Illuminate\Http\Request;

class PackageController extends Controller
{
    
     public function index(Request $request)
    {
        $query = Package::query();

        // Laravel 8 Note: $request->boolean() safely handles
        // '1','true','on','yes' input for tinyint active column
        if ($request->has('active')) {
            $query->where('active', $request->boolean('active') ? 1 : 0);
        }

        if ($request->filled('project_id')) {
            $query->where('project_id', (int) $request->input('project_id'));
        }

        if ($request->filled('search')) {
            $query->where('packagename', 'LIKE', '%' . $request->input('search') . '%');
        }

        // withCount() is fully supported in L8.
        // Adds 'customers_count' attribute to each Package instance.
        $packages = $query
            ->withCount('customers')
            ->orderBy('packagename', 'asc')
            ->paginate((int) $request->input('per_page', 15))
            ->withQueryString(); // L8: keep filter params in pagination links

        return response()->json([
            'success'    => true,
            'data'       => $packages->items(),
            'pagination' => [
                'total'         => $packages->total(),
                'per_page'      => $packages->perPage(),
                'current_page'  => $packages->currentPage(),
                'last_page'     => $packages->lastPage(),
                'next_page_url' => $packages->nextPageUrl(),
                'prev_page_url' => $packages->previousPageUrl(),
            ],
        ]);
    }
}
