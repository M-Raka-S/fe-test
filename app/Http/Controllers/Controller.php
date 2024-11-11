<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Project;

abstract class Controller
{
    protected function getAllowedProjectIds()
    {
        return Project::where('user_id', auth()->user()->id)
            ->pluck('id')
            ->toArray();
    }

    protected function validateRequest(Request $request, array $rules)
    {
        $validator = validator()->make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
        return null;
    }

    protected function loadSubtasks($tasks)
    {
        foreach ($tasks as $task) {
            $task->subtasks = $this->loadSubtasks($task->subtasks);
        }
        return $tasks;
    }
}
