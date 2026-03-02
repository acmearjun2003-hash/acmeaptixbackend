<?php

namespace App\Http\Controllers;

use App\Models\CustomerMaster;
use Illuminate\Http\Request;

class CustomerMasterController extends Controller
{
    public function indexPage()
    {
        return view('customers.index');
    }


    public function index(Request $request)
    {
        $query = CustomerMaster::query();

        if ($request->filled('name')) {
            $query->where('NAME', 'LIKE', '%' . $request->name . '%');
        }

        if ($request->filled('customertype')) {
            $query->where('CUSTOMERTYPE', $request->customertype);
        }

        if ($request->filled('packagetype')) {
            $query->where('PACKAGETYPE', $request->packagetype);
        }

        $perPage = $request->get('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 10;

        return response()->json($query->paginate($perPage));
    }

    public function show(int $owncode)
    {
        $customer = CustomerMaster::query()->findOrFail($owncode);

        return response()->json($customer);
    }

    public function getCustomerTypes()
    {
        $customerTypes = CustomerMaster::select('CUSTOMERTYPE')
            ->distinct()
            ->whereNotNull('CUSTOMERTYPE')
            ->orderBy('CUSTOMERTYPE')
            ->get()
            ->pluck('CUSTOMERTYPE');

        return response()->json($customerTypes);
    }

    public function getPackageTypes()
    {
        $packageTypes = CustomerMaster::select('PACKAGETYPE')
            ->distinct()
            ->whereNotNull('PACKAGETYPE')
            ->orderBy('PACKAGETYPE')
            ->get()
            ->pluck('PACKAGETYPE');

        return response()->json($packageTypes);
    }
}
