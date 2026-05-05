<?php

namespace App\Http\Controllers;

use App\Models\Family;
use App\Models\Person;
use App\Models\User;
use Illuminate\Http\Request;

class FamilyController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        
        // Families created by user OR shared with user. (Grouped to allow search)
        // If Super Admin, show ALL families.
        if ($user->isSuperAdmin()) {
            $query = Family::query();
        } else {
            $query = Family::where(function($q) use ($user) {
                $q->where('created_by', $user->id)
                  ->orWhereHas('sharedUsers', function($sq) use ($user) {
                      $sq->where('user_id', $user->id);
                  });
            });
        }
        
        $query->withCount('people')->latest();

        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $families = $query->get();
        return view('families.index', compact('families'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $family = Family::create([
            'name' => $request->name,
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('families.show', $family);
    }

    public function show(Request $request, Family $family)
    {
        // Check access
        if (!auth()->user()->canManage($family)) {
            abort(403);
        }

        $focusedPerson = null; $breadcrumbs = [];
        $roots = $this->getRootsData($request, $family, $focusedPerson, $breadcrumbs);

        return view('families.show', compact('family', 'roots', 'focusedPerson', 'breadcrumbs'));
    }

    private function getRootsData(Request $request, Family $family, &$focusedPerson, &$breadcrumbs)
    {
        $rootId = $request->get('root_id');
        $breadcrumbs = [];
        $focusedPerson = null;

        if ($rootId) {
            $focusedPerson = Person::findOrFail($rootId);
            $roots = collect([$focusedPerson->load('childrenRecursive', 'spouses')]);
            
            // Build breadcrumbs
            $current = $focusedPerson;
            while ($current->parent) {
                $breadcrumbs[] = [
                    'id' => $current->parent->id,
                    'name' => $current->parent->full_name
                ];
                $current = $current->parent;
            }
            $breadcrumbs = array_reverse($breadcrumbs);
        } else {
            $roots = $family->people()
                ->whereNull('parent_id')
                ->with('childrenRecursive', 'spouses')
                ->get();
        }
        return $roots;
    }

    public function showVertical(Request $request, Family $family)
    {
        // Check access
        if (!auth()->user()->canManage($family)) {
            abort(403);
        }
        $focusedPerson = null; $breadcrumbs = [];
        $roots = $this->getRootsData($request, $family, $focusedPerson, $breadcrumbs);
        return view('families.show_vertical', compact('family', 'roots', 'focusedPerson', 'breadcrumbs'));
    }

    public function showCircular(Request $request, Family $family)
    {
        // Check access
        if (!auth()->user()->canManage($family)) {
            abort(403);
        }
        $focusedPerson = null; $breadcrumbs = [];
        $roots = $this->getRootsData($request, $family, $focusedPerson, $breadcrumbs);
        return view('families.show_circular', compact('family', 'roots', 'focusedPerson', 'breadcrumbs'));
    }

    public function showColumns(Request $request, Family $family)
    {
        // Check access
        if (!auth()->user()->canManage($family)) {
            abort(403);
        }
        $focusedPerson = null; $breadcrumbs = [];
        $roots = $this->getRootsData($request, $family, $focusedPerson, $breadcrumbs);
        return view('families.show_columns', compact('family', 'roots', 'focusedPerson', 'breadcrumbs'));
    }

    public function share(Request $request, Family $family)
    {
        // Prepend +998 for validation and lookup
        $request->merge(['phone_number' => '+998' . $request->phone_number]);

        $request->validate([
            'phone_number' => 'required|exists:users,phone_number',
        ]);

        $userToShare = User::where('phone_number', $request->phone_number)->first();

        // Don't share with self
        if ($userToShare->id === auth()->id()) {
            return back()->withErrors(['phone_number' => 'You cannot share with yourself.']);
        }

        // Attach user to family sharing (if not already shared)
        $family->sharedUsers()->syncWithoutDetaching([$userToShare->id]);

        return back()->with('success', 'Family tree shared with ' . $userToShare->name);
    }
}
