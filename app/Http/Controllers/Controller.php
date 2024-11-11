<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

abstract class Controller
{
    protected function findOrFail($model, $id, $relations = [])
    {
        $data = $model::with($relations)->find($id);
        if (!$data) {
            return response()->json('data not found', 404);
        }
        return $data;
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
