<?php

namespace App\Http\Controllers;

use App\Models\QuestionBank;
use Illuminate\Http\Request;

class QuestionBankController extends Controller
{
    /**
     * GET /questions
     * List all questions. Filter by category: ?category=3
     */
    public function index(Request $request)
    {
        $query = QuestionBank::query();

        if ($request->filled('category')) {
            $query->where('CATEGORYCODE', $request->integer('category'));
        }

        return response()->json($query->get());
    }

    /**
     * POST /questions
     * Create a new question.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'CATEGORYCODE'  => 'nullable|integer',
            'QUESTION'      => 'required|string|max:255',
            'OPTION1'       => 'required|string|max:255',
            'OPTION2'       => 'required|string|max:255',
            'OPTION3'       => 'required|string|max:255',
            'OPTION4'       => 'required|string|max:255',
            'CORRECTANSWER' => 'required|integer|in:1,2,3,4',
        ]);

        $question = QuestionBank::create($validated);

        return response()->json($question, 201);
    }

    /**
     * GET /questions/{id}
     * Show a single question.
     */
    public function show(int $id)
    {
        $question = QuestionBank::findOrFail($id);

        return response()->json($question);
    }

    /**
     * PUT /questions/{id}
     * Update a question.
     */
    public function update(Request $request, int $id)
    {
        $question = QuestionBank::findOrFail($id);

        $validated = $request->validate([
            'CATEGORYCODE'  => 'nullable|integer',
            'QUESTION'      => 'sometimes|string|max:255',
            'OPTION1'       => 'sometimes|string|max:255',
            'OPTION2'       => 'sometimes|string|max:255',
            'OPTION3'       => 'sometimes|string|max:255',
            'OPTION4'       => 'sometimes|string|max:255',
            'CORRECTANSWER' => 'sometimes|integer|in:1,2,3,4',
        ]);

        $question->update($validated);

        return response()->json($question);
    }

    /**
     * DELETE /questions/{id}
     * Delete a question.
     */
    public function destroy(int $id)
    {
        $question = QuestionBank::findOrFail($id);
        $question->delete();

        return response()->json(['message' => 'Question deleted successfully.']);
    }

    /**
     * GET /questions/random?count=30&category=1
     * Fetch N random questions for an exam.
     */
    public function random(Request $request)
    {
        $request->validate([
            'count'    => 'integer|min:1|max:100',
            'category' => 'nullable|integer',
        ]);

        $count = $request->integer('count', 30);

        $query = QuestionBank::query();

        if ($request->filled('category')) {
            $query->where('CATEGORYCODE', $request->integer('category'));
        }

        // Return options without correct answer so front-end doesn't expose it
        $questions = $query->inRandomOrder()
            ->limit($count)
            ->get(['QBOWNCODE', 'CATEGORYCODE', 'QUESTION', 'OPTION1', 'OPTION2', 'OPTION3', 'OPTION4']);

        return response()->json($questions);
    }
}