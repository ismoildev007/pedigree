<?php

namespace App\Http\Controllers;

use App\Models\ContestSetting;
use App\Models\Student;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function index(ContestSetting $contest)
    {
        $students = Student::orderBy('votes', 'desc')->get();

        return view('students.index', compact('contest', 'students'));
    }
    public function create()
    {
        return view('students.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'mentor_name' => 'nullable|string|max:255',
        ]);

        $lastContest = ContestSetting::latest()->first();

        Student::create([
            'contest_id' => $lastContest->id ?? 1,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'mentor_name' => $request->mentor_name,
        ]);

        return redirect()->route('students.index')->with('success', 'Student muvaffaqiyatli qo‘shildi!');
    }

    public function edit($id)
    {
        $student = Student::findOrFail($id);
        return view('students.edit', compact('student'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'votes' => 'required|integer|min:0',
        ]);

        $student = Student::findOrFail($id);
        $student->update([
            'votes' => $request->votes,
        ]);

        return redirect()->route('students.index')->with('success', 'Talaba ovozlari muvaffaqiyatli yangilandi!');
    }

    public function destroyStudent(Student $student)
    {
        $contest = $student->contest;
        $student->delete();
        return redirect()->route('students.index', $contest)->with('success', 'Talaba o‘chirildi.');
    }
}
