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
        return response()->json(Task::all(), 200);
    }

    public function pick($id)
    {
        $data = $this->findOrFail(Task::class, $id, ['subtasks' => function ($query) {
            $query->with('subtasks');
        }]);

        if ($data instanceof \Illuminate\Http\JsonResponse) {
            return $data;
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
        $data = $this->findOrFail(Task::class, $id);
        if ($data instanceof \Illuminate\Http\JsonResponse) {
            return $data;
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
        $data = $this->findOrFail(Task::class, $id);
        if ($data instanceof \Illuminate\Http\JsonResponse) {
            return $data;
        }

        $done = !$data->done;
        $data->update(['done' => $done]);
        return response()->json('task toggled', 200);
    }

    public function remove($id)
    {
        $data = $this->findOrFail(Task::class, $id);
        if ($data instanceof \Illuminate\Http\JsonResponse) {
            return $data;
        }

        $mors = $data;
        $data->delete();
        return response()->json($mors, 200);
    }
}
