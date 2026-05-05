<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\FamilyController;
use App\Http\Controllers\PersonController;
use Illuminate\Support\Facades\Route;
Route::get('/', function () {
    return redirect()->route('families.index');
});

Route::get('/lang/{locale}', function ($locale) {
    if (in_array($locale, ['en', 'ru', 'uz', 'oz'])) {
        session(['locale' => $locale]);
    }
    return redirect()->back();
})->name('lang.switch');


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
    Route::get('/families/{family}/vertical', [FamilyController::class, 'showVertical'])->name('families.showVertical');
    Route::get('/families/{family}/circular', [FamilyController::class, 'showCircular'])->name('families.showCircular');
    Route::get('/families/{family}/columns', [FamilyController::class, 'showColumns'])->name('families.showColumns');
    Route::get('/families/{family}/workspace', [FamilyController::class, 'showWorkspace'])->name('families.showWorkspace');
    Route::post('/families/{family}/share', [FamilyController::class, 'share'])->name('families.share');

    Route::post('/people', [PersonController::class, 'store'])->name('people.store');
    Route::put('/people/{person}', [PersonController::class, 'update'])->name('people.update');
    Route::post('/people/{person}/position', [PersonController::class, 'updatePosition'])->name('people.updatePosition');
    Route::delete('/people/{person}', [PersonController::class, 'destroy'])->name('people.destroy');
    Route::post('/people/{person}/add-spouse', [PersonController::class, 'addSpouse'])->name('people.add-spouse');
    Route::get('/people/{person}/potential-spouses', [PersonController::class, 'searchPotentialSpouses'])->name('people.potential-spouses');
});