<?php

test('the shared dashboard and each module page render without Task A services or tables', function (string $uri, string $heading) {
    $this->get($uri)
        ->assertOk()
        ->assertSee('FeedbackTrace')
        ->assertSee($heading)
        ->assertSee('DEMO DATA');
})->with([
    'overview' => ['/', 'Good morning, Admin'],
    'feedback' => ['/feedback', 'Feedback'],
    'feedback review' => ['/feedback-review', 'Feedback Review'],
    'AI overview' => ['/ai-overview', 'AI Overview'],
    'task a' => ['/task-a', 'Task A Analysis'],
    'task b' => ['/task-b', 'Task B Analysis'],
    'documents' => ['/documents', 'Documents'],
    'recommendations' => ['/recommendations', 'Recommendations'],
]);
