<?php

namespace App\Http\Controllers;

use App\Models\Clientes;
use App\Models\Survey;
use App\Models\SurveyResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SurveyController extends Controller
{
    private function getActiveSurvey(): Survey
    {
        return Survey::where('is_active', true)->first()
            ?? Survey::create([
                'title' => 'Encuesta de Satisfacción del Gimnasio',
                'is_active' => true,
            ]);
    }

    public function show($slug)
    {
        $survey = $this->getActiveSurvey();
        $cliente = Clientes::where('slug', $slug)->firstOrFail();

        return view('survey.show', compact('survey', 'cliente'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'training_time' => 'required|in:less_1_month,1_to_3_months,3_to_6_months,more_6_months,more_1_year',
            'nps_score' => 'required|integer|between:0,10',
            'nps_reason' => 'required|string|max:500',
            'servqual_ratings.tangibles' => 'required|in:excellent,very_good,good,needs_improvement,poor',
            'servqual_ratings.reliability' => 'required|in:excellent,very_good,good,needs_improvement,poor',
            'servqual_ratings.responsiveness' => 'required|in:excellent,very_good,good,needs_improvement,poor',
            'servqual_ratings.security' => 'required|in:excellent,very_good,good,needs_improvement,poor',
            'servqual_ratings.empathy' => 'required|in:excellent,very_good,good,needs_improvement,poor',
            'open_answers.essential_aspect' => 'required|string|max:500',
            'open_answers.valued_moment' => 'required|string|max:500',
            'open_answers.improvement_suggestion' => 'required|string|max:500',
            'open_answers.disappointing_moment' => 'nullable|string|max:500',
            'open_answers.describing_word' => 'required|string|max:100',
            'open_answers.additional_comments' => 'nullable|string|max:1000',
        ]);

        $survey = $this->getActiveSurvey();

        $response = new SurveyResponse();
        $response->user_id = Auth::id();
        $response->survey_id = $survey->id;
        $response->training_time = $validated['training_time'];
        $response->nps_score = $validated['nps_score'];
        $response->nps_reason = $validated['nps_reason'];
        $response->servqual_ratings = [
            'tangibles' => $validated['servqual_ratings']['tangibles'],
            'reliability' => $validated['servqual_ratings']['reliability'],
            'responsiveness' => $validated['servqual_ratings']['responsiveness'],
            'security' => $validated['servqual_ratings']['security'],
            'empathy' => $validated['servqual_ratings']['empathy'],
        ];
        $response->open_answers = [
            'essential_aspect' => $validated['open_answers']['essential_aspect'],
            'valued_moment' => $validated['open_answers']['valued_moment'],
            'improvement_suggestion' => $validated['open_answers']['improvement_suggestion'],
            'disappointing_moment' => $validated['open_answers']['disappointing_moment'] ?? null,
            'describing_word' => $validated['open_answers']['describing_word'],
            'additional_comments' => $validated['open_answers']['additional_comments'] ?? null,
        ];
        $response->save();

        return redirect()->route('survey.thanks');
    }

    public function thanks()
    {
        return view('survey.gracias');
    }
}
