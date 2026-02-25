<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\Request;

class StudentController extends Controller
{

    public function index(Request $request)
    {
        $students = Student::select('name', 'id')->get();

        if (!empty($students)) {
            return response()->json([
                'status' => true,
                'message' => 'student data found',
                'data' => $students
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'no data found'
            ]);
        }
    }
}
