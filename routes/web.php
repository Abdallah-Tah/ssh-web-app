<?php

use Spatie\Ssh\Ssh;
use App\Livewire\SSHApp;
use phpseclib3\Net\SSH2;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SSHController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\MonitoringController;
use App\Http\Controllers\FileManagerController;
use App\Http\Controllers\ServerConfigController;
use Illuminate\Support\Facades\Process;

Route::get('/', function () {
    $result = Process::input('Hello World')->run('ssh intellink@143.42.185.246');
    dd($result->output());

    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::view('about', 'about')->name('about');

    Route::get('users', [UserController::class, 'index'])->name('users.index');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/ssh', SSHApp::class)->name('ssh.index');

    Route::resource('server-configs', ServerConfigController::class);

    Route::get('/monitoring', [MonitoringController::class, 'index'])->name('monitoring.index');

    Route::get('/file-manager', [FileManagerController::class, 'index'])->name('file-manager.index');
});

Route::get('/ssh-app', SSHApp::class)->name('ssh-app');

require __DIR__.'/auth.php';
