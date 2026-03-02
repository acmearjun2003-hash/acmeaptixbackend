<?php

namespace App\Http\Controllers;

use App\Models\CustomerMaster;
use App\Models\OcfMaster;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Request;

class OcfMasterController extends Controller
{

    public function index()
    {
        $key = "AcmeInfovision@1994#";

        return response()->stream(function () use ($key) {

            // total count
            $total = DB::table('srno_ocf_master as m')
                ->leftJoin('srno_ocf_modules as mod', 'mod.ocfcode', '=', 'm.id')
                ->count();

            echo '{"success":true,"total_rows":' . $total . ',"data":[';
            if (ob_get_level() > 0) ob_flush();
            flush();

            $renderedRows = 0;

            // cursor() = 1 query, streams row by row
            $rows = DB::table('srno_ocf_master as m')
                ->leftJoin('srno_customer_master as c', 'c.id', '=', 'm.customercode')
                ->leftJoin('srno_company_master as co', 'co.id', '=', 'm.companycode')
                ->leftJoin('srno_ocf_modules as mod', 'mod.ocfcode', '=', 'm.id')
                ->leftJoin('srno_acme_module as am', 'am.id', '=', 'mod.modulecode')
                ->leftJoin('srno_acme_module_type as amt', 'amt.id', '=', 'mod.moduletypes')
                ->select(
                    'm.id',
                    'm.Series',
                    'm.DocNo',
                    'm.ocf_date',
                    'm.AmountTotal',
                    'm.active',
                    'm.ispassed',
                    'm.customercode',
                    DB::raw("CAST(AES_DECRYPT(UNHEX(c.name), '$key') AS CHAR) AS customername"),
                    'm.companycode',
                    DB::raw("CAST(AES_DECRYPT(UNHEX(co.companyname), '$key') AS CHAR)"),
                    'mod.id as module_id',
                    'mod.modulename',
                    'mod.quantity',
                    'mod.unit',
                    'mod.amount',
                    'mod.startDate',
                    'mod.endDate',
                    'mod.status',
                    'mod.modulecode',
                    'am.ModuleName',
                    'mod.moduletypes',
                    'amt.moduletype as moduletypename'
                )
                ->orderBy('m.id', 'desc')
                ->cursor();

            foreach ($rows as $row) {
                echo json_encode($row) . "\n";   // ONLY JSON per line
                $renderedRows++;
                // flush every 50 rows to client
                if ($renderedRows % 50 === 0) {
                    if (ob_get_level() > 0) ob_flush();
                    flush();
                }
            }

            if (ob_get_level() > 0) ob_flush();
            flush();
        }, 200, [
            'Content-Type'      => 'application/json',
            'X-Accel-Buffering' => 'no',
            'Cache-Control'     => 'no-cache',
        ]);
    }


    public function chunkFetch(Request $request)
    {
        $page  = (int) $request->get('page', 1);
        $limit = (int) $request->get('limit', 50);

        $total = OcfMaster::count();

        $ocf = OcfMaster::with([
            'customer:id,decrypted_name',
            'company:id,decrypted_company',
            'modules' => function ($q) {
                $q->select(
                    'id as module_id',
                    'ocfcode',
                    'modulename',
                    'quantity',
                    'unit',
                    'amount',
                    'startDate',
                    'endDate',
                    'status',
                    'modulecode',
                    'moduletypes'
                )
                ->with([
                    'module:id,ModuleName',        // acme module
                    'moduleType:id,moduletype'     // module type
                ]);
            }
        ])
        ->select([
            'id',
            'Series',
            'DocNo',
            'ocf_date',
            'AmountTotal',
            'active',
            'ispassed',
            'customercode',
            'companycode'
        ])
        ->orderBy('id', 'DESC')
        ->skip(($page - 1) * $limit)
        ->take($limit)
        ->get();

        return response()->json([
            "success"       => true,
            "data"          => $ocf,
            "total"         => $total,
            "per_page"      => $limit,
            "current_page"  => $page,
            "last_page"     => ceil($total / $limit)
        ]);
    }

        public function fetchData(Request $request)
    {
        $page  = (int) $request->get('page', 1);
        $limit = (int) $request->get('limit', 100);
        $offset = (int) $request->get('offset', 0);

        $total = CustomerMaster::count();

        $ocf = CustomerMaster::orderBy('OWNCODE', 'DESC')
        ->skip(($page - 1) * $limit)
        ->offset($offset)
        ->take($limit)
        ->get();

        return response()->json($ocf);
    }
}
