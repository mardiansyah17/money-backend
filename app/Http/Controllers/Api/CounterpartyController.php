<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Counterparty;
use Illuminate\Http\Request;

class CounterpartyController extends Controller
{
    public function index(Request $request)
    {
        $query = Counterparty::where('user_id', $request->user()->id);

        if ($type = $request->query('type')) {
            $query->where('type', $type);
        }

        $counterparties = $query->orderBy('name')->get();

        return response()->json($counterparties);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', 'string', 'max:50'],
            'phone' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:100'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $counterparty = Counterparty::create([
            'user_id' => $request->user()->id,
            'name' => $data['name'],
            'type' => $data['type'],
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'note' => $data['note'] ?? null,
        ]);

        return response()->json($counterparty, 201);
    }

    public function update(Request $request, Counterparty $counterparty)
    {
        abort_if($counterparty->user_id !== $request->user()->id, 403);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:100'],
            'type' => ['sometimes', 'string', 'max:50'],
            'phone' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:100'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $counterparty->fill($data);
        $counterparty->save();

        return response()->json($counterparty);
    }
}
