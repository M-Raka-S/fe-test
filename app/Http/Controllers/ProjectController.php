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
        return response()->json(Project::where('user_id', auth()->user()->id)->get(), 200);
    }

    public function pick($id)
    {
        $allowedProjectIds = $this->getAllowedProjectIds();
        $data = Project::where('id', $id)
            ->whereIn('id', $allowedProjectIds)
            ->with([
                'tasks' => function ($query) {
                    $query->with('subtasks')->where('parent_id', null);
                },
            ])
            ->first();

        if (!$data) {
            return response()->json('data does not exist or is not under user\'s scope', 404);
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
        $allowedProjectIds = $this->getAllowedProjectIds();

        $data = Project::where('id', $id)->whereIn('id', $allowedProjectIds)->first();

        if (!$data) {
            return response()->json('Project not found or not within user\'s allowed projects', 404);
        }

        $validationError = $this->validateRequest($request, [
            'name' => 'required',
            'description' => 'required',
            'status' => ['required', new Enum(Statuses::class)],
            'user_id' => 'required',
        ]);

        if ($validationError) {
            return $validationError;
        }

        $data->update([
            'name' => $request->name,
            'description' => $request->description,
            'status' => $request->status,
            'user_id' => $request->user_id,
        ]);

        return response()->json($data, 200);
    }

    public function remove($id)
    {
        $allowedProjectIds = $this->getAllowedProjectIds();

        $data = Project::where('id', $id)->whereIn('id', $allowedProjectIds)->first();

        if (!$data) {
            return response()->json('Project not found or not within user\'s allowed projects', 404);
        }

        $mors = $data;
        $data->delete();
        return response()->json($mors, 200);
    }
}
