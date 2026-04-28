<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\FamilyController;
use App\Http\Controllers\PersonController;
use Illuminate\Support\Facades\Route;
Route::get('/', function () {
    return redirect()->route('families.index');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    
    Route::get('/families', [FamilyController::class, 'index'])->name('families.index');
    Route::post('/families', [FamilyController::class, 'store'])->name('families.store');
    Route::get('/families/{family}', [FamilyController::class, 'show'])->name('families.show');
    Route::post('/families/{family}/share', [FamilyController::class, 'share'])->name('families.share');

    Route::post('/people', [PersonController::class, 'store'])->name('people.store');
    Route::put('/people/{person}', [PersonController::class, 'update'])->name('people.update');
    Route::delete('/people/{person}', [PersonController::class, 'destroy'])->name('people.destroy');
    Route::post('/people/{person}/add-spouse', [PersonController::class, 'addSpouse'])->name('people.add-spouse');
    Route::get('/people/{person}/potential-spouses', [PersonController::class, 'searchPotentialSpouses'])->name('people.potential-spouses');
});