<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\CompareController;
use App\Http\Controllers\DeveloperController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\FormController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\PropertyController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SeoController;
use App\Http\Controllers\SubmitPropertyController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

// Discovery
Route::get('/properties', [PropertyController::class, 'index'])->name('properties.index');
Route::get('/properties/{purpose}', [PropertyController::class, 'index'])->whereIn('purpose', ['for-sale', 'for-rent'])->name('properties.purpose');
Route::get('/properties/{slug}', [PropertyController::class, 'show'])->name('properties.show');
Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
Route::get('/projects/{status}', [ProjectController::class, 'index'])->whereIn('status', ['upcoming', 'ongoing', 'ready', 'completed'])->name('projects.status');
Route::get('/projects/{slug}', [ProjectController::class, 'show'])->name('projects.show');
Route::get('/developers', [DeveloperController::class, 'index'])->name('developers.index');
Route::get('/developers/{slug}', [DeveloperController::class, 'show'])->name('developers.show');
Route::get('/locations', [LocationController::class, 'index'])->name('locations.index');
Route::get('/locations/{path}', [LocationController::class, 'show'])->where('path', '[a-z0-9\-/]+')->name('locations.show');
Route::get('/search', [SearchController::class, 'index'])->name('search');
Route::get('/search/suggest', [SearchController::class, 'suggest'])->middleware('throttle:60,1')->name('search.suggest');

// Content
Route::get('/services', [PageController::class, 'services'])->name('services');
Route::get('/about', [PageController::class, 'about'])->name('about');
Route::get('/contact', [PageController::class, 'contact'])->name('contact');
Route::get('/faq', [PageController::class, 'faq'])->name('faq');
Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/category/{category}', [BlogController::class, 'index'])->name('blog.category');
Route::get('/blog/{slug}', [BlogController::class, 'show'])->name('blog.show');
Route::get('/{page}', [PageController::class, 'legal'])->whereIn('page', ['privacy-policy', 'terms-and-conditions', 'cookie-policy', 'disclaimer'])->name('legal');

// Forms (all rate-limited; all create leads through CreateLead)
Route::middleware('throttle:inquiry')->group(function () {
    Route::post('/inquiry', [FormController::class, 'inquiry'])->name('inquiry.store');
    Route::post('/site-visit', [FormController::class, 'siteVisit'])->name('site-visit.store');
    Route::post('/requests/{kind}', [FormController::class, 'submitRequest'])->whereIn('kind', ['sell', 'rent-out', 'valuation', 'assistance'])->name('requests.store');
    Route::post('/properties/{slug}/report', [FormController::class, 'report'])->name('properties.report');
    Route::post('/newsletter', [FormController::class, 'newsletter'])->name('newsletter.store');
});
Route::get('/sell', [FormController::class, 'form'])->defaults('kind', 'sell')->name('forms.sell');
Route::get('/rent-out', [FormController::class, 'form'])->defaults('kind', 'rent-out')->name('forms.rent');
Route::get('/valuation', [FormController::class, 'form'])->defaults('kind', 'valuation')->name('forms.valuation');
Route::get('/assistance', [FormController::class, 'form'])->defaults('kind', 'assistance')->name('forms.assistance');

// Favorites & compare (guests via session, members via database)
Route::get('/favorites', [FavoriteController::class, 'index'])->name('favorites');
Route::post('/favorites/{kind}/{id}', [FavoriteController::class, 'toggle'])->whereIn('kind', ['property', 'project'])->whereNumber('id')->middleware('throttle:120,1')->name('favorites.toggle');
Route::get('/compare', [CompareController::class, 'index'])->name('compare');
Route::post('/compare/{id}', [CompareController::class, 'toggle'])->whereNumber('id')->middleware('throttle:120,1')->name('compare.toggle');
Route::post('/compare/clear', [CompareController::class, 'clear'])->name('compare.clear');

// SEO
Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [SeoController::class, 'robots'])->name('robots');

// Auth
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');

    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])->middleware('throttle:register')->name('register.store');

    Route::get('/forgot-password', [PasswordResetController::class, 'requestForm'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendLink'])->middleware('throttle:password-reset')->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'resetForm'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'reset'])->middleware('throttle:password-reset')->name('password.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('/verify-email', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/verify-email/{id}/{hash}', [EmailVerificationController::class, 'verify'])->middleware('signed')->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])->middleware('throttle:verification')->name('verification.send');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/submit-property', [SubmitPropertyController::class, 'create'])->name('forms.submit');
    Route::post('/submit-property', [SubmitPropertyController::class, 'store'])->middleware('throttle:inquiry')->name('forms.submit.store');
});

Route::middleware(['auth', 'verified'])->prefix('account')->name('account.')->group(function () {
    Route::get('/', [AccountController::class, 'index'])->name('index');
    Route::get('/favorites', [AccountController::class, 'favorites'])->name('favorites');
    Route::get('/inquiries', [AccountController::class, 'inquiries'])->name('inquiries');
    Route::get('/site-visits', [AccountController::class, 'visits'])->name('visits');
    Route::get('/submissions', [AccountController::class, 'submissions'])->name('submissions');
    Route::get('/recently-viewed', [AccountController::class, 'recent'])->name('recent');
    Route::get('/saved-searches', [AccountController::class, 'searches'])->name('searches');
    Route::post('/saved-searches', [AccountController::class, 'storeSearch'])->name('searches.store');
    Route::delete('/saved-searches/{id}', [AccountController::class, 'destroySearch'])->whereNumber('id')->name('searches.destroy');
    Route::get('/profile', [AccountController::class, 'profile'])->name('profile');
    Route::put('/profile', [AccountController::class, 'updateProfile'])->name('profile.update');
});
