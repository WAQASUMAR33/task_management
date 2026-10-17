<?php

namespace Database\Seeders;

use App\Models\Task;
use App\Models\TaskSubtask;
use Illuminate\Database\Seeder;

class SubtaskSeeder extends Seeder
{
    public function run(): void
    {
        $tasks = Task::all();

        $defaultItems = [
            ['title' => 'Analyze requirements and verify architectural specifications', 'completed' => true],
            ['title' => 'Implement deliverable components and integration points', 'completed' => true],
            ['title' => 'Perform security testing & cross-browser QA verification', 'completed' => false],
            ['title' => 'Deployment readiness check & operational sign-off', 'completed' => false],
        ];

        foreach ($tasks as $task) {
            if ($task->subtasks()->count() === 0) {
                foreach ($defaultItems as $idx => $item) {
                    $isDone = $task->status === 'completed' ? true : ($task->status === 'in_progress' ? ($idx < 2) : ($idx === 0));
                    TaskSubtask::create([
                        'task_id' => $task->id,
                        'title' => $item['title'],
                        'is_completed' => $isDone,
                        'created_by' => $task->created_by,
                        'sort_order' => $idx + 1,
                    ]);
                }
            }
        }
    }
}
