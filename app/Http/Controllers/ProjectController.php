<?php

namespace App\Http\Controllers;

use Illuminate\Validation\Rules\Enum;
use Illuminate\Http\Request;
use App\Models\Project;
use App\Statuses;

class ProjectController extends Controller
{
    public function read()
    {
        return response()->json(Project::all(), 200);
    }

    public function pick($id)
    {
        $data = $this->findOrFail(Project::class, $id, ['tasks' => function ($query) {
            $query->with('subtasks')->where('parent_id', null);
        }]);

        if ($data instanceof \Illuminate\Http\JsonResponse) {
            return $data;
        }

        $this->loadSubtasks($data->tasks);
        return response()->json($data, 200);
    }

    public function make(Request $request)
    {
        $validationError = $this->validateRequest($request, [
            'name' => 'required',
            'description' => 'required',
            'status' => ['required', new Enum(Statuses::class)],
        ]);

        if ($validationError) {
            return $validationError;
        }

        $data = Project::create([
            'name' => $request->name,
            'description' => $request->description,
            'status' => $request->status,
            'user_id' => auth()->user()->id,
        ]);
        return response()->json($data, 200);
    }

    public function edit(Request $request, $id)
    {
        $data = $this->findOrFail(Project::class, $id);
        if ($data instanceof \Illuminate\Http\JsonResponse) {
            return $data;
        }

        $validationError = $this->validateRequest($request, [
            'name' => 'required',
            'description' => 'required',
            'status' => ['required', new Enum(Statuses::class)],
        ]);

        if ($validationError) {
            return $validationError;
        }

        $data->update([
            'name' => $request->name,
            'description' => $request->description,
            'status' => $request->status,
        ]);

        return response()->json($data, 200);
    }

    public function remove($id)
    {
        $data = $this->findOrFail(Project::class, $id);
        if ($data instanceof \Illuminate\Http\JsonResponse) {
            return $data;
        }

        $mors = $data;
        $data->delete();
        return response()->json($mors, 200);
    }
}
