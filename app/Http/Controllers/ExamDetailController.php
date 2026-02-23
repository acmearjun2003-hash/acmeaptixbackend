<?php

namespace App\Http\Controllers;

use App\Models\ExamDetail;
use Illuminate\Http\Request;

class ExamDetailController extends Controller
{
    /**
     * GET /exam-details?exam_id=42
     * List all detail rows for a given exam session.
     */
    public function index(Request $request)
    {
        $request->validate(['exam_id' => 'required|integer']);

        $details = ExamDetail::with('question')
            ->where('EMOWNCODE', $request->integer('exam_id'))
            ->get();

        return response()->json($details);
    }

    /**
     * GET /exam-details/{id}
     * Show one answer row with its question, exam session, and candidate.
     */
    public function show(int $id)
    {
        $detail = ExamDetail::with(['examMaster', 'question', 'candidate:id,name,email'])
            ->where('EDOWNCODE', $id)
            ->firstOrFail();

        return response()->json($detail);
    }

    /**
     * PUT /exam-details/{id}
     * Update the user's answer for a single detail row (during live exam).
     */
    public function update(Request $request, int $id)
    {
        $validated = $request->validate([
            'USERANSWER' => 'required|integer|in:1,2,3,4',
        ]);

        $detail = ExamDetail::where('EDOWNCODE', $id)->firstOrFail();

        // Prevent updates after exam is completed
        if ($detail->examMaster?->COMPLETEDSTATUS) {
            return response()->json(['message' => 'Cannot update answers for a completed exam.'], 422);
        }

        $detail->update($validated);

        return response()->json([
            'detail'     => $detail,
            'is_correct' => $detail->isCorrect(),
        ]);
    }

    /**
     * GET /candidates/{candidateId}/exam-details
     * All answer rows across all exams for a specific candidate.
     */
    public function byCandidate(int $candidateId)
    {
        $details = ExamDetail::with(['examMaster', 'question'])
            ->where('CANDIDATEID', $candidateId)
            ->get();

        return response()->json($details);
    }
}