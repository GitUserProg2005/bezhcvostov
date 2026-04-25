<?php

use App\Http\Controllers\CounterController;
use App\Http\Controllers\AiChatController;
use App\Http\Controllers\FolderController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\VideoCallController;
use App\Http\Controllers\InsightController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\ControlRequestController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ChatController;
use App\Services\Prometheus\Metrics;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Prometheus\RenderTextFormat;

// Тест WebSocket — счётчик (без БД, кэш + ShouldBroadcastNow)
Route::get('/counter', [CounterController::class, 'index'])->name('counter');
Route::post('/counter/increment', [CounterController::class, 'increment'])->name('counter.increment');

// Главная всегда ведет на список задач (для гостя middleware auth отправит на login).
Route::get('/', function () {
    return redirect()->route('tasks.index');
})->middleware('auth')->name('home');

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Публичный просмотр профиля по id (нужен для Avatar в Sidebar и др.)
Route::get('/profile/{user}', [ProfileController::class, 'profile'])->name('user.profile')->middleware('auth');

Route::middleware('auth')->group(function () {
    // Tasks routes
    Route::get('/tasks', [TaskController::class, 'index'])->name('tasks.index');
    Route::get('/tasks/get', [TaskController::class, 'getTasks'])->name('tasks.get');
    Route::get('/tasks/{task}', [TaskController::class, 'show'])->name('tasks.show');

    Route::post('/tasks/create-form', [TaskController::class, 'createFormTasks'])->name('tasks.create-form');
    Route::post('/tasks/create-via-voice', [TaskController::class, 'createViaVoice'])->name('tasks.create-via-voice');
    Route::post('/tasks/create-via-photo', [TaskController::class, 'createViaPhoto'])->name('tasks.create-via-photo');
    Route::post('/tasks/update', [TaskController::class, 'updateTask'])->name('tasks.update');
    Route::post('/tasks/delete', [TaskController::class, 'deleteTask'])->name('tasks.delete');
    Route::post('/tasks/update-status', [TaskController::class, 'updateTaskStatus'])->name('tasks.update-status');
    Route::post('/subtasks/update', [TaskController::class, 'updateSubtask'])->name('subtasks.update');
    Route::post('/subtasks/delete', [TaskController::class, 'deleteSubtask'])->name('subtasks.delete');
    Route::post('/subtasks/update-status', [TaskController::class, 'updateSubtaskStatus'])->name('subtasks.update-status');
    Route::post('/subtasks/set-done', [TaskController::class, 'setSubtaskDone'])->name('subtasks.set-done');

    // Folders routes
    Route::get('/folders', [FolderController::class, 'index'])->name('folders.index');
    Route::get('/folders/tree', [FolderController::class, 'tree'])->name('folders.tree');
    Route::post('/folders/create', [FolderController::class, 'create'])->name('folders.create');
    Route::post('/folders/update-title', [FolderController::class, 'updateTitle'])->name('folders.update-title');
    Route::post('/folders/delete', [FolderController::class, 'delete'])->name('folders.delete');

    // Video call routes
    Route::post('/video/signal', [VideoCallController::class, 'signal'])->name('video.signal');
    Route::get('/video-rooms/index', [VideoCallController::class, 'index'])->name('video.index.room');
    Route::post('/create-video-room', [VideoCallController::class, 'createRoom'])->name('video.create.room');
    Route::get('/call/{linkId}', [VideoCallController::class, 'joinRoom'])->name('video.join.room');
    Route::post('/video-insights/create-from-audio', [InsightController::class, 'createFromAudio'])->name('video.insights.create-from-audio');
    Route::get('/video-insights', [InsightController::class, 'index'])->name('video.insights.index');
    Route::get('/video-insights/{insight}', [InsightController::class, 'show'])->name('video.insights.show');

    // Notes routes
    Route::get('/notes/get', [NoteController::class, 'getNotes'])->name('notes.get');
    Route::get('/notes/{note}', [NoteController::class, 'show'])->name('notes.show');
    Route::post('/notes/create', [NoteController::class, 'create'])->name('notes.create');
    Route::post('/notes/update-title', [NoteController::class, 'updateTitle'])->name('notes.update-title');
    Route::post('/notes/update', [NoteController::class, 'update'])->name('notes.update');
    Route::post('/notes/delete', [NoteController::class, 'delete'])->name('notes.delete');

    // AI chat routes
    Route::get('/ai/messages', [AiChatController::class, 'getAiMessages'])->name('ai.messages.get');
    Route::post('/ai/messages/process', [AiChatController::class, 'processMessage'])->name('ai.messages.process');

    Route::get('/sandfox', [GameController::class, 'index'])->name('game.index');
    Route::get('/game/slot-items', [ItemController::class, 'getSlotItems'])->name('game.slot-items');
    Route::get('/game/items', [ItemController::class, 'getItems'])->name('game.items');
    Route::post('/game/items/add', [ItemController::class, 'addItemToUser'])->name('game.items.add');

    Route::get('/control-request/search', [ControlRequestController::class, 'searchByAccountCode'])
        ->name('control.request.search');
    Route::post('/control-request', [ControlRequestController::class, 'createControlRequest'])
        ->name('send.control.request');
    Route::get('/control-request/received-pending', [ControlRequestController::class, 'pendingReceived'])
        ->name('control.request.pending');
    Route::post('/control-request/{controlRequest}/status', [ControlRequestController::class, 'updateStatusRequest'])
        ->name('control.request.update-status');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');

    Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
    Route::get('/projects/get', [ProjectController::class, 'getProjects'])->name('projects.get');
    Route::get('/projects/{project}', [ProjectController::class, 'show'])->name('projects.show');
    Route::post('/projects', [ProjectController::class, 'create'])->name('projects.create');
    Route::delete('/projects/{project}', [ProjectController::class, 'delete'])->name('projects.delete');
    Route::post('/projects/{project}/users', [ProjectController::class, 'addUserToProject'])->name('projects.users.add');
    Route::get('/projects/{project}/users/search', [ProjectController::class, 'searchUsers'])->name('projects.users.search');
    Route::get('/projects/{project}/chats/{chat}', [ChatController::class, 'index'])->name('projects.chats.index');
    Route::get('/projects/{project}/chats/{chat}/messages', [ChatController::class, 'getMessages'])->name('projects.chats.messages');
    Route::post('/projects/{project}/chats/{chat}/messages', [ChatController::class, 'addMessage'])->name('projects.chats.messages.add');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Prometheus metrics (без web middleware — без сессий/CSRF для scrape).
Route::get('/metrics', function (Metrics $metrics) {
    return response($metrics->render(), 200)
        ->header('Content-Type', RenderTextFormat::MIME_TYPE);
})->withoutMiddleware(['web']);

require __DIR__.'/auth.php';
