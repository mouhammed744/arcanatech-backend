<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\TeacherController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\RfidController;
use App\Http\Controllers\Api\UniversityController;
use App\Http\Controllers\Api\FiliereController;
use App\Http\Controllers\Api\TemporaryCodeController;
use App\Http\Controllers\Api\ScheduleController;
use App\Http\Controllers\Api\ClassroomController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\TwoFactorController;
use App\Http\Controllers\Api\StatsController;
use App\Http\Controllers\Api\ReportsController;

/**
 * Routes API - Système de Gestion Universitaire UniAccess
 *
 * SÉCURITÉ :
 * - JWT HMAC-SHA256 via middleware jwt.auth
 * - Rate limiting intégré dans AuthController
 * - CORS configuré dans bootstrap/app.php
 * - Multi-tenant par university_id
 */

// ============== ROUTES PUBLIQUES (pas d'authentification) ==============

Route::get('universities', [UniversityController::class, 'index'])->name('universities.index');

// Filières publiques — pour le formulaire d'inscription mobile (pas d'auth requise)
Route::get('universities/{universityId}/filieres-public', [FiliereController::class, 'publicList'])
    ->name('universities.filieres.public');

Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login'])->name('auth.login');
    Route::post('register', [AuthController::class, 'register'])->name('auth.register');
    Route::post('register-student', [AuthController::class, 'registerStudent'])->name('auth.register-student');
    Route::post('refresh', [AuthController::class, 'refresh'])->name('auth.refresh');
    Route::post('forgot-password', [AuthController::class, 'forgotPassword'])->name('auth.forgot-password');
    Route::post('reset-password', [AuthController::class, 'resetPassword'])->name('auth.reset-password');

    // 2FA — endpoints publics (challenge post-login)
    Route::prefix('2fa')->group(function () {
        Route::post('send-code', [TwoFactorController::class, 'sendCode'])->name('auth.2fa.send-code');
        Route::post('verify',    [TwoFactorController::class, 'verify'])  ->name('auth.2fa.verify');
    });

    // OAuth Social Login
    Route::post('social/login', [AuthController::class, 'socialLogin'])->name('auth.social.login');

    Route::get('{provider}/redirect', [AuthController::class, 'oauthRedirect'])
        ->where('provider', 'google')
        ->name('auth.oauth.redirect');
    Route::get('{provider}/callback', [AuthController::class, 'oauthCallback'])
        ->where('provider', 'google')
        ->name('auth.oauth.callback');

});

// ============== ROUTES PROTÉGÉES (authentification JWT requise) ==============

Route::middleware(['jwt.auth'])->group(function () {

    // -------- AUTHENTIFICATION --------
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::get('me', [AuthController::class, 'me'])->name('auth.me');
        Route::post('change-password', [AuthController::class, 'changePassword'])->name('auth.change-password');
        Route::post('avatar', [AuthController::class, 'updateAvatar'])->name('auth.avatar.update');
        Route::delete('avatar', [AuthController::class, 'deleteAvatar'])->name('auth.avatar.delete');
        Route::patch('select-university', [AuthController::class, 'selectUniversity'])->name('auth.select-university');

        // 2FA — endpoints proteges (gestion par l utilisateur connecte)
        Route::prefix('2fa')->group(function () {
            Route::get('status',          [TwoFactorController::class, 'status'])     ->name('auth.2fa.status');
            Route::post('setup',          [TwoFactorController::class, 'setup'])      ->name('auth.2fa.setup');
            Route::post('confirm',        [TwoFactorController::class, 'confirm'])    ->name('auth.2fa.confirm');
            Route::post('enable-email',   [TwoFactorController::class, 'enableEmail'])->name('auth.2fa.enable-email');
            Route::delete('/',            [TwoFactorController::class, 'disable'])    ->name('auth.2fa.disable');
        });
    });

    // -------- NOTIFICATIONS & FCM (mobile) --------
    Route::prefix('me')->group(function () {
        // Notifications
        Route::get('notifications',                  [NotificationController::class, 'index'])       ->name('notifications.index');
        Route::post('notifications/read-all',        [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
        Route::post('notifications/{id}/read',       [NotificationController::class, 'markAsRead'])  ->name('notifications.read');
        Route::delete('notifications/{id}',          [NotificationController::class, 'destroy'])     ->name('notifications.destroy');

        // Token FCM
        Route::post('fcm-token',   [NotificationController::class, 'storeFcmToken']) ->name('fcm-token.store');
        Route::delete('fcm-token', [NotificationController::class, 'deleteFcmToken'])->name('fcm-token.delete');
    });

    // -------- STATISTIQUES GLOBALES (tableau de bord admin) --------
    Route::prefix('stats')->group(function () {
        Route::get('kpis',                   [StatsController::class, 'kpis'])                ->name('stats.kpis');
        Route::get('punctuality-over-time',  [StatsController::class, 'punctualityOverTime']) ->name('stats.punctuality-over-time');
        Route::get('student-rankings',       [StatsController::class, 'studentRankings'])     ->name('stats.student-rankings');
        Route::get('by-course',              [StatsController::class, 'byCourse'])            ->name('stats.by-course');
    });

    // -------- UNIVERSITÉS --------
    Route::get('universities/{id}', [UniversityController::class, 'show'])->name('universities.show');

    // -------- COURS --------
    Route::prefix('universities/{universityId}/courses')->group(function () {
        Route::get('/', [CourseController::class, 'index'])->name('courses.index');
        Route::get('{courseId}', [CourseController::class, 'show'])->name('courses.show');
        Route::post('/', [CourseController::class, 'store'])
            ->middleware('can:create,App\\Models\\Course')
            ->name('courses.store');
        Route::put('{courseId}', [CourseController::class, 'update'])
            ->middleware('can:update,courseId')
            ->name('courses.update');
        Route::delete('{courseId}', [CourseController::class, 'destroy'])
            ->middleware('can:delete,courseId')
            ->name('courses.destroy');
    });

    // -------- ENSEIGNANTS --------
    Route::prefix('universities/{universityId}/teachers')->group(function () {
        Route::get('/',              [TeacherController::class, 'index'])  ->name('teachers.index');
        Route::post('/',             [TeacherController::class, 'store'])  ->name('teachers.store');
        Route::get('{teacherId}',    [TeacherController::class, 'show'])   ->name('teachers.show');
        Route::put('{teacherId}',    [TeacherController::class, 'update']) ->name('teachers.update');
        Route::delete('{teacherId}', [TeacherController::class, 'destroy'])->name('teachers.destroy');
    });

    // -------- ÉTUDIANTS --------
    Route::prefix('universities/{universityId}/students')->group(function () {
        Route::get('/',              [StudentController::class, 'index'])  ->name('students.index');
        Route::post('/',             [StudentController::class, 'store'])  ->name('students.store');
        Route::get('{studentId}',    [StudentController::class, 'show'])   ->name('students.show');
        Route::put('{studentId}',    [StudentController::class, 'update']) ->name('students.update');
        Route::delete('{studentId}', [StudentController::class, 'destroy'])->name('students.destroy');
        Route::get('{studentId}/attendances', [StudentController::class, 'attendances'])
            ->name('students.attendances');
        Route::get('{studentId}/timetable', [StudentController::class, 'timetable'])
            ->name('students.timetable');
    });

    // -------- PRÉSENCES --------
    Route::prefix('universities/{universityId}/attendances')->group(function () {
        Route::get('/', [AttendanceController::class, 'index'])->name('attendances.index');
        Route::get('{attendanceId}', [AttendanceController::class, 'show'])->name('attendances.show');
    });

    // ════════════════════════════════════════════════════
    // ════════ FILIÈRES + MISE À JOUR EN MASSE ═════════
    // ════════════════════════════════════════════════════
    Route::prefix('universities/{universityId}/filieres')->group(function () {
        Route::get('/', [FiliereController::class, 'index'])->name('filieres.index');
        Route::get('{filiereId}', [FiliereController::class, 'show'])->name('filieres.show');
        Route::post('/', [FiliereController::class, 'store'])->name('filieres.store');
        Route::put('{filiereId}', [FiliereController::class, 'update'])->name('filieres.update');
        Route::delete('{filiereId}', [FiliereController::class, 'destroy'])->name('filieres.destroy');

        Route::post('bulk-update-schedule', [FiliereController::class, 'bulkUpdateSchedule'])
            ->name('filieres.bulk-update-schedule');
        Route::post('bulk-update-cards', [FiliereController::class, 'bulkUpdateCards'])
            ->name('filieres.bulk-update-cards');
    });

    // ════════════════════════════════════════════════════
    // ═══════════════ SALLES DE COURS ═══════════════════
    // ════════════════════════════════════════════════════
    Route::get('universities/{universityId}/classrooms', [ClassroomController::class, 'index'])
        ->name('classrooms.index');

    // ════════════════════════════════════════════════════
    // ═══════════════ EMPLOI DU TEMPS ═══════════════════
    // ════════════════════════════════════════════════════
    Route::prefix('universities/{universityId}/schedule')->group(function () {
        Route::get('/',      [ScheduleController::class, 'index'])  ->name('schedule.index');
        Route::post('/',     [ScheduleController::class, 'store'])  ->name('schedule.store');
        Route::put('{id}',   [ScheduleController::class, 'update']) ->name('schedule.update');
        Route::delete('{id}',[ScheduleController::class, 'destroy'])->name('schedule.destroy');
    });

    // ════════════════════════════════════════════════════
    // ═══════════════ RFID / ACCÈS ══════════════════════
    // ════════════════════════════════════════════════════
    Route::prefix('universities/{universityId}/rfid')->group(function () {
        // Lecteur matériel (pas d'auth JWT — sécurisé par réseau local)
        Route::post('scan', [RfidController::class, 'scan'])
            ->withoutMiddleware('jwt.auth')
            ->name('rfid.scan');

        // Admin : attribution de carte à un étudiant
        Route::post('assign',              [RfidController::class, 'assign'])     ->name('rfid.assign');
        Route::patch('{cardId}/toggle',    [RfidController::class, 'toggleCard']) ->name('rfid.toggle');
        Route::delete('{cardId}',          [RfidController::class, 'deleteCard']) ->name('rfid.delete');

        // Admin : forcer l'accès d'un étudiant en retard
        Route::post('admin-override', [RfidController::class, 'adminOverride'])
            ->name('rfid.admin-override');

        // Admin : historique des scans
        Route::get('logs', [RfidController::class, 'logs'])
            ->middleware('can:viewAny,App\\Models\\AccessLog')
            ->name('rfid.logs');
    });

    // ════════════════════════════════════════════════════
    // ════════════════ RAPPORTS ═════════════════════════
    // ════════════════════════════════════════════════════
    Route::prefix('reports')->group(function () {
        Route::post('generate', [ReportsController::class, 'generate'])->name('reports.generate');
        Route::get('history',   [ReportsController::class, 'history']) ->name('reports.history');
    });

    // ════════════════════════════════════════════════════
    // ═══════════ CODES TEMPORAIRES ═══════════════════════
    // ════════════════════════════════════════════════════
    Route::prefix('universities/{universityId}/temporary-codes')->group(function () {
        Route::get('/', [TemporaryCodeController::class, 'index'])->name('temporary-codes.index');
        Route::post('/', [TemporaryCodeController::class, 'store'])->name('temporary-codes.store');
        Route::delete('{id}', [TemporaryCodeController::class, 'destroy'])->name('temporary-codes.destroy');

        // Public endpoint for code validation (used by door readers)
        Route::post('validate', [TemporaryCodeController::class, 'validateCode'])
            ->withoutMiddleware('jwt.auth')
            ->name('temporary-codes.validate');
    });

});
