<?php

namespace App\Http\Controllers;

use App\Models\Person;
use App\Models\Family;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PersonController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'family_id' => 'required|exists:families,id',
            'parent_id' => 'nullable|exists:people,id',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'gender' => 'required|in:male,female',
            'birth_year' => 'nullable|integer|min:1000|max:' . date('Y'),
            'death_year' => 'nullable|integer|min:1000|max:' . date('Y'),
            'photo' => 'nullable|image|max:2048',
            'description' => 'nullable|string',
        ]);

        $family = Family::findOrFail($validated['family_id']);
        if (!auth()->user()->canManage($family)) {
            abort(403);
        }

        if ($request->hasFile('photo')) {
            $validated['photo'] = $request->file('photo')->store('people', 'public');
        }

        Person::create($validated);

        return back()->with('success', 'Person added successfully.');
    }

    public function update(Request $request, Person $person)
    {
        if (!auth()->user()->canManage($person->family)) {
            abort(403);
        }

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'gender' => 'required|in:male,female',
            'birth_year' => 'nullable|integer|min:1000|max:' . date('Y'),
            'death_year' => 'nullable|integer|min:1000|max:' . date('Y'),
            'photo' => 'nullable|image|max:2048',
            'description' => 'nullable|string',
        ]);

        if ($request->hasFile('photo')) {
            if ($person->photo) {
                Storage::disk('public')->delete($person->photo);
            }
            $validated['photo'] = $request->file('photo')->store('people', 'public');
        }

        $person->update($validated);

        return back()->with('success', 'Person updated successfully.');
    }

    public function destroy(Person $person)
    {
        if (!auth()->user()->canManage($person->family)) {
            abort(403);
        }

        $person->delete();
        return back()->with('success', 'Person and descendants deleted successfully.');
    }

    public function addSpouse(Request $request, Person $person)
    {
        if (!auth()->user()->canManage($person->family)) {
            abort(403);
        }

        $request->validate([
            'spouse_id' => 'required|exists:people,id',
        ]);

        // Symmetric sync
        $person->spouses()->syncWithoutDetaching([$request->spouse_id]);
        Person::find($request->spouse_id)->spouses()->syncWithoutDetaching([$person->id]);

        return back()->with('success', 'Spouse linked successfully.');
    }

    public function searchPotentialSpouses(Person $person)
    {
        $query = Person::where('family_id', $person->family_id)
            ->where('id', '!=', $person->id)
            ->where('gender', '!=', $person->gender);

        if ($person->birth_year) {
            // "O'zi tengi va o'zidan kichik" means birthYear >= person's birthYear
            $query->where('birth_year', '>=', $person->birth_year);
        }

        // Sort by birth year (oldest first) then name
        $results = $query->orderBy('birth_year', 'asc')
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'birth_year', 'gender']);

        return response()->json($results);
    }
}
