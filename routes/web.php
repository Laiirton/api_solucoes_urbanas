<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
})->name('home');

// Dashboard original (pode ser removido depois)
Route::view('dashboard-old', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard-old');

// Backoffice Routes
use App\Http\Controllers\BackofficeController;

Route::middleware(['auth', 'verified', 'backoffice'])->prefix('backoffice')->name('backoffice.')->group(function () {
    Route::get('/', [BackofficeController::class, 'dashboard'])->name('dashboard');
    
    // Service Requests
    Route::get('/service-requests', [BackofficeController::class, 'serviceRequests'])->name('service-requests.index');
    Route::get('/service-requests/{id}', [BackofficeController::class, 'showServiceRequest'])->name('service-requests.show');
    Route::patch('/service-requests/{id}/status', [BackofficeController::class, 'updateServiceRequestStatus'])->name('service-requests.update-status');
    
    // Uploads
    Route::get('/uploads', [BackofficeController::class, 'uploads'])->name('uploads.index');
    
    // Users
    Route::get('/users', [BackofficeController::class, 'users'])->name('users.index');
    Route::get('/users/create', [BackofficeController::class, 'createUser'])->name('users.create');
    Route::post('/users', [BackofficeController::class, 'storeUser'])->name('users.store');
    Route::get('/users/{id}', [BackofficeController::class, 'showUser'])->name('users.show');
    Route::get('/users/{id}/edit', [BackofficeController::class, 'editUser'])->name('users.edit');
    Route::patch('/users/{id}', [BackofficeController::class, 'updateUser'])->name('users.update');
    Route::delete('/users/{id}', [BackofficeController::class, 'deleteUser'])->name('users.delete');
});

// Redirecionar dashboard para backoffice
Route::get('/dashboard', function () {
    return redirect()->route('backoffice.dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');
});

require __DIR__.'/auth.php';
