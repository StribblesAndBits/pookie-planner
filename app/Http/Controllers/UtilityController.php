<?php

namespace App\Http\Controllers;

use App\Models\Utility;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UtilityController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $utilities = Utility::query()
            ->where('user_id', $request->user()->id)
            ->orderBy('due_date')
            ->orderBy('id')
            ->get();

        return response()->json($utilities);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate($this->storeValidationRules());

        $utility = Utility::query()->create([
            ...$validated,
            'user_id' => $request->user()->id,
            'status' => $validated['status'] ?? 'unpaid',
            'recurs_monthly' => (bool) ($validated['recurs_monthly'] ?? false),
        ]);

        return response()->json($utility, 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $utility = Utility::query()
            ->where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $validated = $request->validate($this->updateValidationRules());
        $payload = [...$validated];

        if (array_key_exists('recurs_monthly', $payload)) {
            $payload['recurs_monthly'] = (bool) $payload['recurs_monthly'];
        }

        $utility->update($payload);

        return response()->json($utility);
    }

    private function storeValidationRules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'tag' => ['required', Rule::in(Utility::TAGS)],
            'due_date' => 'required|date_format:Y-m-d',
            'amount' => 'required|numeric|min:0|max:99999999.99',
            'status' => ['nullable', Rule::in(Utility::STATUSES)],
            'recurs_monthly' => 'nullable|boolean',
        ];
    }

    private function updateValidationRules(): array
    {
        return [
            'name' => 'sometimes|required|string|max:255',
            'tag' => ['sometimes', 'required', Rule::in(Utility::TAGS)],
            'due_date' => 'sometimes|required|date_format:Y-m-d',
            'amount' => 'sometimes|required|numeric|min:0|max:99999999.99',
            'status' => ['sometimes', 'required', Rule::in(Utility::STATUSES)],
            'recurs_monthly' => 'sometimes|required|boolean',
        ];
    }
}
