<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CandidateController;
use App\Http\Controllers\Api\EmployerController;
use App\Http\Controllers\Api\HomeController;
use App\Http\Controllers\Api\JobController;
use App\Http\Middleware\ApiTokenAuth;
use Illuminate\Support\Facades\Route;

Route::get('/home.php', [HomeController::class, 'index']);
Route::get('/home', [HomeController::class, 'index']);

Route::post('/auth/login.php', [AuthController::class, 'login']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/register.php', [AuthController::class, 'register']);
Route::post('/auth/register', [AuthController::class, 'register']);

Route::get('/jobs/list.php', [JobController::class, 'index']);
Route::get('/jobs/list', [JobController::class, 'index']);
Route::get('/jobs/detail.php', [JobController::class, 'show'])->defaults('id', 0);
Route::get('/jobs/detail.php/{id?}', [JobController::class, 'show']);
Route::get('/jobs/{id}', [JobController::class, 'show']);

Route::middleware([ApiTokenAuth::class])->group(function () {
    Route::get('/auth/me.php', [AuthController::class, 'me']);
    Route::get('/auth/me', [AuthController::class, 'me']);
});

Route::middleware([ApiTokenAuth::class . ':candidate'])->group(function () {
    Route::post('/jobs/apply.php', [JobController::class, 'apply']);
    Route::post('/jobs/apply', [JobController::class, 'apply']);
    Route::post('/jobs/bookmark.php', [JobController::class, 'bookmark']);
    Route::post('/jobs/bookmark', [JobController::class, 'bookmark']);

    Route::get('/candidate/dashboard.php', [CandidateController::class, 'dashboard']);
    Route::get('/candidate/dashboard', [CandidateController::class, 'dashboard']);
    Route::match(['get', 'post'], '/candidate/profile.php', [CandidateController::class, 'profile']);
    Route::match(['get', 'post'], '/candidate/profile', [CandidateController::class, 'profile']);
    Route::get('/candidate/applications.php', [CandidateController::class, 'applications']);
    Route::get('/candidate/applications', [CandidateController::class, 'applications']);
    Route::match(['get', 'post'], '/candidate/bookmarks.php', [CandidateController::class, 'bookmarks']);
    Route::match(['get', 'post'], '/candidate/bookmarks', [CandidateController::class, 'bookmarks']);
});

Route::middleware([ApiTokenAuth::class . ':employer'])->group(function () {
    Route::get('/employer/dashboard.php', [EmployerController::class, 'dashboard']);
    Route::get('/employer/dashboard', [EmployerController::class, 'dashboard']);
    Route::match(['get', 'post'], '/employer/jobs.php', [EmployerController::class, 'jobs']);
    Route::match(['get', 'post'], '/employer/jobs', [EmployerController::class, 'jobs']);
    Route::match(['get', 'post'], '/employer/applicants.php', [EmployerController::class, 'applicants']);
    Route::match(['get', 'post'], '/employer/applicants', [EmployerController::class, 'applicants']);
});

Route::middleware([ApiTokenAuth::class . ':admin'])->group(function () {
    Route::get('/admin/dashboard.php', [AdminController::class, 'dashboard']);
    Route::get('/admin/dashboard', [AdminController::class, 'dashboard']);
    Route::match(['get', 'post'], '/admin/users.php', [AdminController::class, 'users']);
    Route::match(['get', 'post'], '/admin/users', [AdminController::class, 'users']);
    Route::match(['get', 'post'], '/admin/jobs.php', [AdminController::class, 'jobs']);
    Route::match(['get', 'post'], '/admin/jobs', [AdminController::class, 'jobs']);
});
