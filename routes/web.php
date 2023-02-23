<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Freelancer\FreelancerController;
use App\Http\Controllers\PagesController;
use App\Http\Controllers\ChartController;
use App\Http\Controllers\User\UserController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});
Route::get('/employer_account', function () {
    return view('auth.register-employer');
});

Route::post('/createEmployer', [PagesController::class, 'createEmployer'])->name('createEmployer');
Route::post('/createFreelancer', [PagesController::class, 'createFreelancer'])->name('createFreelancer');

Route::get('/home', function () {
    Auth::logout();
    return redirect()->route('login');
    // return view('welcome');
});
Auth::routes();

// Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

Route::middleware(['auth', 'role:superadmin'])->name('superadmin.')->prefix('superadmin')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'superadmindash'])->name('dashboard');
    Route::get('/createcategory', [AdminController::class, 'createcategory'])->name('create.category');
    Route::any('/createJobCategory', [AdminController::class, 'createJobcategory'])->name('createJobCategory');
    Route::get('/alljobcategories', [AdminController::class, 'alljobcategories'])->name('alljobcategories');
    Route::get('/deletejobcategory/{id}', [AdminController::class, 'deletejobcategory'])->name('deletejobcategory');
    Route::get('/alljobs', [AdminController::class, 'alljobs'])->name('alljobs');
    Route::get('/viewjob/{id}', [AdminController::class, 'viewjob'])->name('viewjob');
    Route::get('/deletejob/{id}', [AdminController::class, 'deletejob'])->name('deletejob');

    Route::get('/allcompletejobs', [AdminController::class, 'allcompletejobs'])->name('allcompletejobs');
    Route::get('/jobsinprogress', [AdminController::class, 'jobsinprogress'])->name('jobsinprogress');
    Route::get('/jobsasdrafts', [AdminController::class, 'jobsasdrafts'])->name('jobsasdrafts');

    Route::get('/allpayments', [AdminController::class, 'allpayments'])->name('allpayments');
    
    Route::get('/all-employers', [AdminController::class, 'allemployers'])->name('all-employers');
    Route::get('/all-freelancers', [AdminController::class, 'allfreelancers'])->name('all-freelancers');

});


Route::middleware(['auth', 'role:freelancer'])->name('freelancer.')->prefix('freelancer')->group(function () {
    Route::get('/dashboard', [FreelancerController::class, 'freelancerdash'])->name('dashboard');
    Route::get('/all-jobs', [FreelancerController::class, 'alljobs'])->name('postedjobs');
    Route::get('/single-job/{id}', [FreelancerController::class, 'singlejob'])->name('singlejob');
    Route::get('/completejobs', [FreelancerController::class, 'completejobs'])->name('completejobs');
    Route::get('/pendingjobs', [FreelancerController::class, 'pendingjobs'])->name('pendingjobs');
    Route::get('/allpayments', [FreelancerController::class, 'allpayments'])->name('allpayments');
    Route::get('/completepayments', [FreelancerController::class, 'completepayments'])->name('completepayments');
    Route::get('/pendingpayments', [FreelancerController::class, 'pendingpayments'])->name('pendingpayments');
    Route::get('/disputedpayments', [FreelancerController::class, 'disputedpayments'])->name('disputedpayments');
});

Route::middleware(['auth', 'role:user'])->name('user.')->prefix('user')->group(function () {
    Route::get('/dashboard', [UserController::class, 'userdash'])->name('dashboard');
    Route::get('/new-job', [UserController::class, 'newJob'])->name('new-job');
    Route::get('/publish-task/{slug}', [UserController::class, 'publishjob'])->name('publish-job');
    Route::get('/new-job-attachment/{slug}', [UserController::class, 'newJobattachments'])->name('new-job-attachments');
    Route::get('/alljobs', [UserController::class, 'allJobs'])->name('alljobs');
    Route::get('/draft-jobs', [UserController::class, 'draftjobs'])->name('draftjobs');
    Route::get('jobs/single-job/{id}', [UserController::class, 'singleJob'])->name('single-job');
    Route::post('jobs/update-single-job/{id}', [UserController::class, 'UpdateSingleJob'])->name('update-single-job');
    Route::get('jobs/delete-single-job/{id}', [UserController::class, 'DeleteSingleJob'])->name('delete-single-job');
    Route::get('/jobsinprogress', [UserController::class, 'jobsinprogress'])->name('jobsinprogress');
    Route::get('/completejobs', [UserController::class, 'completejobs'])->name('completejobs');
    Route::get('/allpayments', [UserController::class, 'allpayments'])->name('allpayments');
});
