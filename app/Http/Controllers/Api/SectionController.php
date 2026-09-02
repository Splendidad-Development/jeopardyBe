<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSectionRequest;
use App\Http\Resources\QuestionResource;
use App\Http\Resources\SectionResource;
use App\Models\Section;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class SectionController extends Controller
{
    /**
     * Display a listing of active sections.
     */
    public function index(): AnonymousResourceCollection
    {
        $sections = Section::where('is_active', true)
            ->withCount('questions')
            ->orderBy('order')
            ->get();

        return SectionResource::collection($sections);
    }

    /**
     * Store a newly created section in storage.
     */
    public function store(StoreSectionRequest $request): JsonResponse
    {
        $maxOrder = Section::max('order') ?? 0;
        $order = $request->input('order', $maxOrder + 1);

        $section = Section::create([
            'title' => $request->string('title'),
            'description' => $request->input('description'),
            'order' => $order,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return (new SectionResource($section))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Display the specified section with its questions.
     */
    public function show(Section $section): SectionResource
    {
        $section->load('questions');

        return new SectionResource($section);
    }

    /**
     * Get all questions for a specific section.
     */
    public function questions(Section $section): AnonymousResourceCollection
    {
        $questions = $section->questions()->orderBy('order')->get();

        return QuestionResource::collection($questions);
    }
}
