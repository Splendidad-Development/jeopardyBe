<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreQuestionRequest;
use App\Http\Resources\QuestionResource;
use App\Models\Question;
use App\Models\Section;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class QuestionController extends Controller
{
    /**
     * Store a newly created question in storage (either directly or within a section).
     */
    public function store(StoreQuestionRequest $request, ?Section $section = null): JsonResponse
    {
        $sectionId = $section?->id ?? $request->input('section_id');

        if (! $sectionId) {
            return response()->json([
                'message' => 'The section_id field is required.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $maxOrder = Question::where('section_id', $sectionId)->max('order') ?? 0;
        $order = $request->input('order', $maxOrder + 1);

        $question = Question::create([
            'section_id' => $sectionId,
            'question_text' => $request->string('question_text'),
            'answer' => $request->input('answer'),
            'points' => $request->input('points', 100),
            'order' => $order,
        ]);

        return (new QuestionResource($question))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Display the specified question.
     */
    public function show(Question $question): QuestionResource
    {
        return new QuestionResource($question);
    }
}
