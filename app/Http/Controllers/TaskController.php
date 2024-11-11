<?php

namespace App\Http\Controllers;

use Illuminate\Validation\Rules\Enum;
use Illuminate\Http\Request;
use App\Models\Task;
use App\Statuses;
use App\Priorities;

class TaskController extends Controller
{
    public function read()
    {
        $allowed = $this->getAllowedProjectIds();
        return response()->json(Task::whereIn('project_id', $allowed)->get(), 200);
    }

    public function pick($id)
    {
        $allowed = $this->getAllowedProjectIds();
        $data = Task::where('id', $id)
            ->whereIn('project_id', $allowed)
            ->with([
                'subtasks' => function ($query) {
                    $query->with('subtasks');
                },
            ])
            ->first();

        if (!$data) {
            return response()->json('data does not exist or is not under user\'s scope', 404);
        }

        $this->loadSubtasks($data->subtasks);
        return response()->json($data, 200);
    }

    public function make(Request $request)
    {
        $validationError = $this->validateRequest($request, [
            'title' => 'required',
            'description' => 'required',
            'due_date' => 'required|date',
            'status' => ['required', new Enum(Statuses::class)],
            'priority' => ['required', new Enum(Priorities::class)],
            'project_id' => 'required|exists:projects,id',
            'parent_id' => 'nullable|exists:tasks,id',
        ]);

        if ($validationError) {
            return $validationError;
        }

        $data = Task::create($request->only(['title', 'description', 'due_date', 'status', 'priority', 'project_id', 'parent_id']));
        return response()->json($data, 200);
    }

    public function edit(Request $request, $id)
    {
        $allowed = $this->getAllowedProjectIds();

        $data = Task::where('id', $id)->whereIn('project_id', $allowed)->first();

        if (!$data) {
            return response()->json('Task not found or not within user\'s allowed projects', 404);
        }

        $validationError = $this->validateRequest($request, [
            'title' => 'required',
            'description' => 'required',
            'due_date' => 'required|date',
            'status' => ['required', new Enum(Statuses::class)],
            'priority' => ['required', new Enum(Priorities::class)],
            'parent_id' => [
                'nullable',
                'exists:tasks,id',
                function ($attribute, $value, $fail) use ($request) {
                    if ($value == $request->id) {
                        $fail('The parent task cannot be the same as the task.');
                    }
                },
            ],
        ]);

        if ($validationError) {
            return $validationError;
        }

        $data->update($request->only(['title', 'description', 'due_date', 'status', 'priority', 'parent_id']));
        return response()->json($data, 200);
    }

    public function toggle_done($id)
    {
        $allowed = $this->getAllowedProjectIds();

        $data = Task::where('id', $id)->whereIn('project_id', $allowed)->first();

        if (!$data) {
            return response()->json('Task not found or not within user\'s allowed projects', 404);
        }

        $done = !$data->done;
        $data->update(['done' => $done]);
        return response()->json('task toggled', 200);
    }

    public function remove($id)
    {
        $allowed = $this->getAllowedProjectIds();

        $data = Task::where('id', $id)->whereIn('project_id', $allowed)->first();

        if (!$data) {
            return response()->json('Task not found or not within user\'s allowed projects', 404);
        }

        $mors = $data;
        $data->delete();
        return response()->json($mors, 200);
    }
}
