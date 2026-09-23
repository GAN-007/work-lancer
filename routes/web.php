<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Freelancer\FreelancerController;
use App\Http\Controllers\PagesController;
use App\Http\Controllers\User\UserController;
use App\Http\Controllers\GalikaController;
use App\Http\Controllers\GalikaHealthController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/',fn()=>view('welcome'));
Route::get('/galika-health',GalikaHealthController::class);
Route::get('/employer_account',fn()=>view('auth.register-employer'));
Route::post('/createEmployer',[PagesController::class,'createEmployer'])->name('createEmployer');
Route::post('/createFreelancer',[PagesController::class,'createFreelancer'])->name('createFreelancer');
Route::get('/home',function(){Auth::logout();return redirect()->route('login');});
Auth::routes();

Route::middleware('auth')->prefix('galika')->name('galika.')->group(function(){
    Route::get('/',[GalikaController::class,'dashboard'])->name('dashboard');
    Route::get('/profile',[GalikaController::class,'profile'])->name('profile');
    Route::post('/profile',[GalikaController::class,'saveProfile'])->name('profile.save');
    Route::get('/opportunities',[GalikaController::class,'opportunities'])->name('opportunities');
    Route::get('/applications',[GalikaController::class,'applications'])->name('applications');
    Route::get('/decisions',[GalikaController::class,'decisions'])->name('decisions');
    Route::post('/decisions/{decision}',[GalikaController::class,'resolveDecision'])->name('decisions.resolve');
    Route::get('/analytics',[GalikaController::class,'analytics'])->name('analytics');
    Route::post('/profile/cv',[GalikaController::class,'uploadCv'])->name('cv.upload');
    Route::get('/documents/{document}/review',[GalikaController::class,'reviewDocument'])->name('documents.review');
    Route::post('/documents/{document}/confirm',[GalikaController::class,'confirmDocument'])->name('documents.confirm');
    Route::get('/connections',[GalikaController::class,'connections'])->name('connections');
    Route::post('/connections/api',[GalikaController::class,'saveApiConnection'])->name('connections.api');
    Route::post('/connections/{provider}/test',[GalikaController::class,'testConnection'])->name('connections.test');
    Route::post('/connections/airtable/base',[GalikaController::class,'selectAirtableBase'])->name('connections.airtable.base');
    Route::get('/oauth/{provider}',[GalikaController::class,'oauthStart'])->name('oauth.start');
    Route::get('/oauth/{provider}/callback',[GalikaController::class,'oauthCallback'])->name('oauth.callback');
    Route::get('/wealth',[GalikaController::class,'wealth'])->name('wealth');
    Route::post('/wealth',[GalikaController::class,'createWealth'])->name('wealth.create');
    Route::get('/campaigns',[GalikaController::class,'campaigns'])->name('campaigns');
    Route::get('/relationships',[GalikaController::class,'relationships'])->name('relationships');
    Route::get('/interviews',[GalikaController::class,'interviews'])->name('interviews');
    Route::get('/offers',[GalikaController::class,'offers'])->name('offers');
    Route::get('/events',[GalikaController::class,'events'])->name('events');
    Route::get('/personas',[GalikaController::class,'personas'])->name('personas');
    Route::post('/applications/{application}/withdraw',[GalikaController::class,'withdrawApplication'])->name('applications.withdraw');
    Route::get('/assist/{token}/resume',[GalikaController::class,'resumeAssist'])->name('assist.resume');
    Route::post('/canary',[GalikaController::class,'canary'])->name('canary');
});

Route::middleware(['auth','role:superadmin'])->name('superadmin.')->prefix('superadmin')->group(function(){
    Route::get('/dashboard',[AdminController::class,'superadmindash'])->name('dashboard');
    Route::get('/createcategory',[AdminController::class,'createcategory'])->name('create.category');
    Route::any('/createJobCategory',[AdminController::class,'createJobcategory'])->name('createJobCategory');
    Route::get('/alljobcategories',[AdminController::class,'alljobcategories'])->name('alljobcategories');
    Route::get('/deletejobcategory/{id}',[AdminController::class,'deletejobcategory'])->name('deletejobcategory');
    Route::get('/alljobs',[AdminController::class,'alljobs'])->name('alljobs');
    Route::get('/viewjob/{id}',[AdminController::class,'viewjob'])->name('viewjob');
    Route::get('/deletejob/{id}',[AdminController::class,'deletejob'])->name('deletejob');
    Route::get('/allcompletejobs',[AdminController::class,'allcompletejobs'])->name('allcompletejobs');
    Route::get('/jobsinprogress',[AdminController::class,'jobsinprogress'])->name('jobsinprogress');
    Route::get('/jobsasdrafts',[AdminController::class,'jobsasdrafts'])->name('jobsasdrafts');
    Route::get('/allpayments',[AdminController::class,'allpayments'])->name('allpayments');
    Route::get('/all-employers',[AdminController::class,'allemployers'])->name('all-employers');
    Route::get('/all-freelancers',[AdminController::class,'allfreelancers'])->name('all-freelancers');
});
Route::middleware(['auth','role:freelancer'])->name('freelancer.')->prefix('freelancer')->group(function(){
    Route::get('/dashboard',[FreelancerController::class,'freelancerdash'])->name('dashboard');
    Route::get('/all-jobs',[FreelancerController::class,'alljobs'])->name('postedjobs');
    Route::get('/single-job/{id}',[FreelancerController::class,'singlejob'])->name('singlejob');
    Route::get('/completejobs',[FreelancerController::class,'completejobs'])->name('completejobs');
    Route::get('/pendingjobs',[FreelancerController::class,'pendingjobs'])->name('pendingjobs');
    Route::get('/allpayments',[FreelancerController::class,'allpayments'])->name('allpayments');
    Route::get('/completepayments',[FreelancerController::class,'completepayments'])->name('completepayments');
    Route::get('/pendingpayments',[FreelancerController::class,'pendingpayments'])->name('pendingpayments');
    Route::get('/disputedpayments',[FreelancerController::class,'disputedpayments'])->name('disputedpayments');
});
Route::middleware(['auth','role:user'])->name('user.')->prefix('user')->group(function(){
    Route::get('/dashboard',[UserController::class,'userdash'])->name('dashboard');
    Route::get('/new-job',[UserController::class,'newJob'])->name('new-job');
    Route::get('/publish-task/{slug}',[UserController::class,'publishjob'])->name('publish-job');
    Route::get('/new-job-attachment/{slug}',[UserController::class,'newJobattachments'])->name('new-job-attachments');
    Route::get('/alljobs',[UserController::class,'allJobs'])->name('alljobs');
    Route::get('/draft-jobs',[UserController::class,'draftjobs'])->name('draftjobs');
    Route::get('jobs/single-job/{id}',[UserController::class,'singleJob'])->name('single-job');
    Route::post('jobs/update-single-job/{id}',[UserController::class,'UpdateSingleJob'])->name('update-single-job');
    Route::get('jobs/delete-single-job/{id}',[UserController::class,'DeleteSingleJob'])->name('delete-single-job');
    Route::get('/jobsinprogress',[UserController::class,'jobsinprogress'])->name('jobsinprogress');
    Route::get('/completejobs',[UserController::class,'completejobs'])->name('completejobs');
    Route::get('/allpayments',[UserController::class,'allpayments'])->name('allpayments');
});
