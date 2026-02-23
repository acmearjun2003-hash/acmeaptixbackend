<?php

namespace App\Http\Controllers;

use App\Models\ExamMaster;
use App\Models\ExamDetail;
use App\Models\QuestionBank;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExamMasterController extends Controller
{
    /**
     * GET /exams
     * List all exam sessions. Filter by candidate: ?candidate_id=262
     */
    public function index(Request $request)
    {
        $query = ExamMaster::with('candidate:id,name,email');

        if ($request->filled('candidate_id')) {
            $query->where('CANDIDATEID', $request->integer('candidate_id'));
        }

        if ($request->boolean('completed')) {
            $query->completed();
        }

        return response()->json($query->latest('EMOWNCODE')->get());
    }

    /**
     * POST /exams/start
     * Start a new exam session for a candidate.
     * Randomly picks questions and seeds examdetails rows.
     */
    public function start(Request $request)
    {
        $validated = $request->validate([
            'candidate_id'   => 'required|exists:users,id',
            'question_count' => 'integer|min:1|max:100',
            'category_code'  => 'nullable|integer',
        ]);

        $questionCount = $validated['question_count'] ?? 30;
        $candidateId   = $validated['candidate_id'];

        DB::beginTransaction();

        try {
            // Create the exam session
            $exam = ExamMaster::create([
                'CANDIDATEID'     => $candidateId,
                'IPADDRESS'       => $request->ip(),
                'EXAMDATE'        => now()->format('Ymd'),
                'EXAMTIME'        => now()->format('His'),
                'COMPLETEDSTATUS' => 0,
                'EXAMSCORE'       => 0,
                'TIMEELAPSED'     => 0,
            ]);

            // Fetch random questions
            $questionQuery = QuestionBank::inRandomOrder()->limit($questionCount);
            if (!empty($validated['category_code'])) {
                $questionQuery->where('CATEGORYCODE', $validated['category_code']);
            }
            $questions = $questionQuery->get();

            // Pre-seed answer rows (USERANSWER = null until submitted)
            $details = $questions->map(fn($q) => [
                'EMOWNCODE'     => $exam->EMOWNCODE,
                'QBOWNCODE'     => $q->QBOWNCODE,
                'CORRECTANSWER' => $q->CORRECTANSWER,
                'USERANSWER'    => null,
                'CANDIDATEID'   => $candidateId,
            ])->toArray();

            ExamDetail::insert($details);

            // Mark user as exam started
            User::where('id', $candidateId)->update(['examstarted' => 1]);

            DB::commit();

            return response()->json([
                'exam'      => $exam,
                'questions' => $questions->map(fn($q) => [
                    'QBOWNCODE' => $q->QBOWNCODE,
                    'QUESTION'  => $q->QUESTION,
                    'OPTION1'   => $q->OPTION1,
                    'OPTION2'   => $q->OPTION2,
                    'OPTION3'   => $q->OPTION3,
                    'OPTION4'   => $q->OPTION4,
                ]),
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to start exam.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /exams/{id}/submit
     * Submit answers for an exam session.
     * Body: { "answers": { "EDOWNCODE": selected_option, ... } }
     */
    public function submit(Request $request, int $id)
    {
        $request->validate([
            'answers'   => 'required|array',
            'answers.*' => 'integer|in:1,2,3,4',
            'time_elapsed' => 'nullable|integer',
        ]);

        $exam = ExamMaster::with('examDetails')->where('EMOWNCODE', $id)->firstOrFail();

        if ($exam->COMPLETEDSTATUS) {
            return response()->json(['message' => 'Exam already submitted.'], 422);
        }

        DB::beginTransaction();

        try {
            $score = 0;

            foreach ($exam->examDetails as $detail) {
                $userAnswer = $request->input("answers.{$detail->EDOWNCODE}");

                if ($userAnswer !== null) {
                    $isCorrect = (int) $userAnswer === (int) $detail->CORRECTANSWER;
                    $detail->update(['USERANSWER' => $userAnswer]);
                    if ($isCorrect) {
                        $score++;
                    }
                }
            }

            $exam->update([
                'COMPLETEDSTATUS' => 1,
                'EXAMSCORE'       => $score,
                'TIMEELAPSED'     => $request->integer('time_elapsed', 0),
            ]);

            // Store score on the user record as well
            User::where('id', $exam->CANDIDATEID)->update([
                'aptiscore'   => $score,
                'examstarted' => 0,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Exam submitted successfully.',
                'score'   => $score,
                'total'   => $exam->examDetails->count(),
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to submit exam.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * GET /exams/{id}
     * Show a single exam session with all answers and questions.
     */
    public function show(int $id)
    {
        $exam = ExamMaster::with([
            'candidate:id,name,email',
            'examDetails.question',
        ])->where('EMOWNCODE', $id)->firstOrFail();

        return response()->json($exam);
    }

    /**
     * GET /exams/{id}/result
     * Show score summary for a completed exam.
     */
    public function result(int $id)
    {
        $exam = ExamMaster::with('examDetails.question')
            ->where('EMOWNCODE', $id)
            ->firstOrFail();

        $details = $exam->examDetails->map(fn($d) => [
            'question'      => $d->question?->QUESTION,
            'your_answer'   => $d->USERANSWER,
            'correct_answer'=> $d->CORRECTANSWER,
            'is_correct'    => $d->isCorrect(),
        ]);

        return response()->json([
            'exam_id'   => $exam->EMOWNCODE,
            'score'     => $exam->EXAMSCORE,
            'total'     => $exam->examDetails->count(),
            'details'   => $details,
        ]);
    }
}