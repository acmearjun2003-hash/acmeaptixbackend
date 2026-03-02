<?php

namespace App\Http\Controllers;

use App\Models\EncCustomerMaster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class EncCustomerMasterController extends Controller
{

    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'packagecode' => 'nullable|integer',
            'name' => 'nullable|string|max:200',
            'city' => 'nullable|string|max:50',
            'phone' => 'nullable|string|max:50',
            'per_page' => 'nullable|integer|min:1|max:500',
            'page' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }
        
        $key = "AcmeInfovision@1994#";

        // Base Query with MySQL Decryption
        $query = DB::table('srno_customer_master AS c')
            ->leftJoin('srno_acme_package AS p', 'c.packagecode', '=', 'p.id')
            ->select([
                'c.id',
                DB::raw("CAST(AES_DECRYPT(UNHEX(c.name), '$key') AS CHAR) AS name"),
                'c.entrycode',
                DB::raw("CAST(AES_DECRYPT(UNHEX(c.email), '$key') AS CHAR) AS email"),
                DB::raw("CAST(AES_DECRYPT(UNHEX(c.phone), '$key') AS CHAR) AS phone"),
                DB::raw("CAST(AES_DECRYPT(UNHEX(c.whatsappno), '$key') AS CHAR) AS whatsappno"),
                DB::raw("CAST(AES_DECRYPT(UNHEX(c.address1), '$key') AS CHAR) AS address1"),
                DB::raw("CAST(AES_DECRYPT(UNHEX(c.address2), '$key') AS CHAR) AS address2"),
                DB::raw("CAST(AES_DECRYPT(UNHEX(c.state), '$key') AS CHAR) AS state"),
                DB::raw("CAST(AES_DECRYPT(UNHEX(c.district), '$key') AS CHAR) AS district"),
                DB::raw("CAST(AES_DECRYPT(UNHEX(c.taluka), '$key') AS CHAR) AS taluka"),
                DB::raw("CAST(AES_DECRYPT(UNHEX(c.city), '$key') AS CHAR) AS city"),
                DB::raw("CAST(AES_DECRYPT(UNHEX(c.concernperson), '$key') AS CHAR) AS concernperson"),
                'c.isverified',
                'c.role_id',
                'c.noofbranch',
                'c.packagecode',
                'p.packagename AS package_name',
                'c.subpackagecode',
                'c.caversion',
                'c.implementation',
                'c.active',
                'c.deactive',
                'c.messageID',
                'c.created_at',
                'c.updated_at',
                'c.customerlanguage',
                'c.DuplicateMarked',
                'c.LongCustomerId',
                'c.UpdateToCallCenter'
            ]);


        if ($request->filled('packagecode')) {
            $query->where('c.packagecode', $request->packagecode);
        }

        if ($request->filled('phone')) {
            // $encryptedPhone = $this->encryptValue($request->phone);
            $encryptedPhone = $request->phone;
            $query->whereRaw(
                "LOWER(CAST(AES_DECRYPT(UNHEX(c.PhoneForIndex), '$key') AS CHAR)) LIKE ?",
                ["%$encryptedPhone%"]
            );
            // $query->where('c.PhoneForIndex', $encryptedPhone);
        }

        if ($request->filled('name')) {
            $searchName = strtolower($request->name);
            // $searchName = $this->encryptValue($request->name);
            // $query->where('c.name', 'Like', $searchName);
            $query->whereRaw(
                "LOWER(CAST(AES_DECRYPT(UNHEX(c.name), '$key') AS CHAR)) LIKE ?",
                ["%$searchName%"]
            );
        }

        if ($request->filled('city')) {
            $searchCity = strtolower($request->city);
            $query->whereRaw(
                "LOWER(CAST(AES_DECRYPT(UNHEX(c.city), '$key') AS CHAR)) LIKE ?",
                ["%$searchCity%"]
            );
        }


        $customers = $query->orderBy('c.id', 'asc')->get();

        return response()->json([
            'success' => true,
            // 'total' => $customers->total(),
            'data' => $customers
           
        ]);
    }

    /**
     * Helper to encrypt a value using the same method as the model
     */
    // private function encryptValue(string $value): string
    // {
    //     $key    = 'AcmeInfovision@1994#';
    //     $keyLen = 16;
    //     $newKey = str_repeat("\0", $keyLen);

    //     foreach (str_split($key) as $i => $char) {
    //         $newKey[$i % $keyLen] = $newKey[$i % $keyLen] ^ $char;
    //     }

    //     $encrypted = openssl_encrypt($value, 'AES-128-ECB', $newKey, OPENSSL_RAW_DATA);
    //     return strtoupper(bin2hex($encrypted));
    // }
}
