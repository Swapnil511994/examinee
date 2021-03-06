<?php

namespace App\Http\Controllers\Admin;

use App\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCoursesRequest;
use App\Http\Requests\Admin\UpdateCoursesRequest;
use App\User;

class CoursesController extends Controller
{


    /**
     * Display a listing of Course.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (request('show_deleted') == 1) {
            $courses = Course::onlyTrashed()->ofTeacher()->get();
        } else {
            $courses = Course::ofTeacher()->get();
        }

        return view('courses.index', compact('courses'));
    }

    /**
     * Show the form for creating new Course.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $teachers = User::whereHas('role', function ($q) { $q->where('id', User::TEACHER); } )->get()->pluck('name', 'id');
        return view('courses.create', compact('teachers'));
    }

    /**
     * Store a newly created Course in storage.
     *
     * @param  \App\Http\Requests\StoreCoursesRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreCoursesRequest $request)
    {
        
        $request = $this->saveFiles($request);
        $course = Course::create($request->all());
        $teachers = \Auth::user()->isAdmin() ? array_filter((array)$request->input('teachers')) : [\Auth::user()->id];
        $course->teachers()->sync($teachers);

        return redirect()->route('courses.index');
    }


    /**
     * Show the form for editing Course.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        
        $teachers = \App\User::whereHas('role', function ($q) { $q->where('role_id', 2); } )->get()->pluck('name', 'id');

        $course = Course::findOrFail($id);

        return view('courses.edit', compact('course', 'teachers'));
    }

    /**
     * Update Course in storage.
     *
     * @param  \App\Http\Requests\UpdateCoursesRequest  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateCoursesRequest $request, $id)
    {
        
        $request = $this->saveFiles($request);
        $course = Course::findOrFail($id);
        $course->update($request->all());
        $teachers = \Auth::user()->isAdmin() ? array_filter((array)$request->input('teachers')) : [\Auth::user()->id];
        $course->teachers()->sync($teachers);

        return redirect()->route('admin.courses.index');
    }


    /**
     * Display Course.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        
        $teachers = \App\User::get()->pluck('name', 'id');$lessons = \App\Lesson::where('course_id', $id)->get();$tests = \App\Test::where('course_id', $id)->get();

        $course = Course::findOrFail($id);

        return view('admin.courses.show', compact('course', 'lessons', 'tests'));
    }


    /**
     * Remove Course from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        
        $course = Course::findOrFail($id);
        $course->delete();

        return redirect()->route('admin.courses.index');
    }

    /**
     * Delete all selected Course at once.
     *
     * @param Request $request
     */
    public function massDestroy(Request $request)
    {
        
        if ($request->input('ids')) {
            $entries = Course::whereIn('id', $request->input('ids'))->get();

            foreach ($entries as $entry) {
                $entry->delete();
            }
        }
    }


    /**
     * Restore Course from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function restore($id)
    {
        
        $course = Course::onlyTrashed()->findOrFail($id);
        $course->restore();

        return redirect()->route('admin.courses.index');
    }

    /**
     * Permanently delete Course from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function perma_del($id)
    {
        $course = Course::onlyTrashed()->findOrFail($id);
        $course->forceDelete();

        return redirect()->route('admin.courses.index');
    }
}
