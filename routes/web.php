<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Events\CommitteeController;
use App\Http\Controllers\Events\EventController;
use App\Http\Controllers\Events\LibraryController;
use App\Http\Controllers\Events\MeetingController;
use App\Http\Controllers\Events\TaskController;
use App\Http\Controllers\Events\WorkController;
use App\Http\Controllers\Master\CommitteeRoleController;
use App\Http\Controllers\Master\DivisionTemplateController;
use App\Http\Controllers\Master\EventCategoryController;
use App\Http\Controllers\Master\OrganizationUnitController;
use App\Http\Controllers\Master\PermissionController;
use App\Http\Controllers\Master\RoleController;
use App\Http\Controllers\Master\UserController;
use App\Http\Controllers\Master\VenueController;
use App\Http\Controllers\Organization\OrganizationProfileController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::name('dashboard.')->middleware(['auth:web', config('jetstream.auth_session'), 'verified'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->middleware('can:dashboard.view')->name('index');
});

Route::post('/profile/update', [ProfileController::class, 'update'])->name('profile.update_ajax')->middleware('auth');

Route::name('master.')->prefix('master')->middleware(['auth:web', config('jetstream.auth_session'), 'verified'])->group(function () {
    // user
    Route::name('user.')->controller(UserController::class)->prefix('user')->group(function () {
        Route::get('/index', 'index')->middleware('can:users.view')->name('index');
        Route::get('/data', 'data')->middleware('can:users.view')->name('data');
        Route::get('/show/{user:id}', 'show')->middleware('can:users.view')->name('show');
        Route::post('/insert', 'insert')->middleware('can:users.create')->name('insert');
        Route::post('/update/{user:id}', 'update')->middleware('can:users.update')->name('update');
        Route::post('/delete/{user:id}', 'delete')->middleware('can:users.delete')->name('delete');
        Route::post('/reset_2fa/{user:id}', 'resetTwoFactor')->middleware(['can:users.manage-access', 'can:users.reset-two-factor'])->name('reset_2fa');
    });

    // role
    Route::name('role.')->controller(RoleController::class)->prefix('role')->group(function () {
        Route::get('/index', 'index')->middleware('can:roles.view')->name('index');
        Route::get('/data', 'data')->middleware('can:roles.view')->name('data');
        Route::get('/show/{role:id}', 'show')->middleware('can:roles.view')->name('show');
        Route::post('/insert', 'insert')->middleware('can:roles.create')->name('insert');
        Route::post('/update/{role:id}', 'update')->middleware('can:roles.update')->name('update');
        Route::post('/delete/{role:id}', 'delete')->middleware('can:roles.delete')->name('delete');
    });

    // permission
    Route::name('permission.')->controller(PermissionController::class)->prefix('permission')->group(function () {
        Route::get('/index', 'index')->middleware('can:permissions.view')->name('index');
        Route::get('/data', 'data')->middleware('can:permissions.view')->name('data');
        Route::get('/detail/{permission:id}', 'detail')->middleware('can:permissions.view')->name('detail');
        Route::post('/insert', 'insert')->middleware('can:permissions.create')->name('insert');
        Route::post('/update/{permission:id}', 'update')->middleware('can:permissions.update')->name('update');
        Route::post('/delete/{permission:id}', 'delete')->middleware('can:permissions.delete')->name('delete');
    });
});

Route::middleware(['auth:web', config('jetstream.auth_session'), 'verified'])->group(function () {
    // organization profile
    Route::name('organization.profile.')->controller(OrganizationProfileController::class)->prefix('organization/profile')->group(function () {
        Route::get('/', 'index')->middleware('can:organization.view')->name('index');
        Route::post('/update', 'update')->middleware('can:organization.update')->name('update');
    });

    Route::middleware('organization.configured')->group(function () {
        Route::name('master.')->prefix('master')->group(function () {
            // organization unit
            Route::name('organization_unit.')->controller(OrganizationUnitController::class)->prefix('organization-unit')->group(function () {
                Route::get('/index', 'index')->middleware('can:organization-units.view')->name('index');
                Route::get('/data', 'data')->middleware('can:organization-units.view')->name('data');
                Route::get('/options', 'options')->middleware('can:organization-units.view')->name('options');
                Route::get('/show/{organizationUnit}', 'show')->middleware('can:organization-units.view')->name('show');
                Route::post('/insert', 'insert')->middleware('can:organization-units.create')->name('insert');
                Route::post('/update/{organizationUnit}', 'update')->middleware('can:organization-units.update')->name('update');
                Route::post('/delete/{organizationUnit}', 'delete')->middleware('can:organization-units.delete')->name('delete');
            });

            // committee role
            Route::name('committee_role.')->controller(CommitteeRoleController::class)->prefix('committee-role')->group(function () {
                Route::get('/index', 'index')->middleware('can:committee-roles.view')->name('index');
                Route::get('/data', 'data')->middleware('can:committee-roles.view')->name('data');
                Route::get('/show/{committeeRole}', 'show')->middleware('can:committee-roles.view')->name('show');
                Route::post('/insert', 'insert')->middleware('can:committee-roles.create')->name('insert');
                Route::post('/update/{committeeRole}', 'update')->middleware('can:committee-roles.update')->name('update');
                Route::post('/delete/{committeeRole}', 'delete')->middleware('can:committee-roles.delete')->name('delete');
            });

            // division template
            Route::name('division_template.')->controller(DivisionTemplateController::class)->prefix('division-template')->group(function () {
                Route::get('/index', 'index')->middleware('can:division-templates.view')->name('index');
                Route::get('/data', 'data')->middleware('can:division-templates.view')->name('data');
                Route::get('/show/{divisionTemplate}', 'show')->middleware('can:division-templates.view')->name('show');
                Route::post('/insert', 'insert')->middleware('can:division-templates.create')->name('insert');
                Route::post('/update/{divisionTemplate}', 'update')->middleware('can:division-templates.update')->name('update');
                Route::post('/delete/{divisionTemplate}', 'delete')->middleware('can:division-templates.delete')->name('delete');
            });

            // event category
            Route::name('event_category.')->controller(EventCategoryController::class)->prefix('event-category')->group(function () {
                Route::get('/index', 'index')->middleware('can:event-categories.view')->name('index');
                Route::get('/data', 'data')->middleware('can:event-categories.view')->name('data');
                Route::get('/show/{eventCategory}', 'show')->middleware('can:event-categories.view')->name('show');
                Route::post('/insert', 'insert')->middleware('can:event-categories.create')->name('insert');
                Route::post('/update/{eventCategory}', 'update')->middleware('can:event-categories.update')->name('update');
                Route::post('/delete/{eventCategory}', 'delete')->middleware('can:event-categories.delete')->name('delete');
            });

            // venue
            Route::name('venue.')->controller(VenueController::class)->prefix('venue')->group(function () {
                Route::get('/index', 'index')->middleware('can:venues.view')->name('index');
                Route::get('/data', 'data')->middleware('can:venues.view')->name('data');
                Route::get('/show/{venue}', 'show')->middleware('can:venues.view')->name('show');
                Route::post('/insert', 'insert')->middleware('can:venues.create')->name('insert');
                Route::post('/update/{venue}', 'update')->middleware('can:venues.update')->name('update');
                Route::post('/delete/{venue}', 'delete')->middleware('can:venues.delete')->name('delete');
            });
        });
    });
});

Route::middleware(['auth:web', config('jetstream.auth_session'), 'verified', 'organization.configured'])
    ->name('events.')
    ->prefix('events')
    ->group(function () {
        Route::get('/', [EventController::class, 'index'])->name('index');
        Route::get('/data', [EventController::class, 'data'])->middleware('can:events.view')->name('data');
        Route::post('/insert', [EventController::class, 'insert'])->middleware('can:events.create')->name('insert');
        Route::get('/people-options', [EventController::class, 'peopleOptions'])->name('people_options');
        Route::post('/{event}/invitation/{response}', [EventController::class, 'respondToInvitation'])
            ->whereIn('response', ['accept', 'decline'])
            ->name('invitation.respond');

        // Everything below belongs to one event: people outside it get "not found", and
        // a division, assignment or membership of another event cannot be reached through it.
        Route::middleware('event.access')->prefix('{event}')->scopeBindings()->group(function () {
            Route::controller(EventController::class)->group(function () {
                Route::get('/', 'overview')->name('overview');
                Route::get('/show', 'show')->name('show');
                Route::post('/update', 'update')->name('update');
                Route::post('/status', 'changeStatus')->name('status');
                Route::post('/phase', 'changePhase')->name('phase');
                Route::get('/people-options', 'eventPeopleOptions')->name('event_people_options');
            });

            Route::controller(CommitteeController::class)->group(function () {
                Route::get('/committee', 'show')->name('committee');
                Route::post('/divisions/insert', 'storeDivision')->name('divisions.insert');
                Route::post('/divisions/from-templates', 'divisionsFromTemplates')->name('divisions.from_templates');
                Route::get('/divisions/{division}/show', 'showDivision')->name('divisions.show');
                Route::post('/divisions/{division}/update', 'updateDivision')->name('divisions.update');
                Route::post('/divisions/{division}/delete', 'deleteDivision')->name('divisions.delete');
                Route::post('/assignments/insert', 'assign')->name('assignments.insert');
                Route::get('/assignments/{assignment}/show', 'showAssignment')->name('assignments.show');
                Route::post('/assignments/{assignment}/update', 'updateAssignment')->name('assignments.update');
                Route::post('/assignments/{assignment}/end', 'endAssignment')->name('assignments.end');
                Route::post('/memberships/{membership}/confirm', 'confirmMembership')->name('memberships.confirm');
                Route::post('/memberships/{membership}/revoke', 'revokeMembership')->name('memberships.revoke');
            });

            Route::controller(WorkController::class)->group(function () {
                Route::get('/work', 'index')->name('work');
                Route::get('/work/data', 'data')->name('work.data');
                Route::get('/work/member-options', 'memberOptions')->name('work.member_options');
                Route::get('/work/task-options', 'taskOptions')->name('work.task_options');
                Route::get('/work/program-options', 'programOptions')->name('work.program_options');
                Route::post('/programs/insert', 'storeProgram')->name('programs.insert');
                Route::get('/programs/{program}/show', 'showProgram')->name('programs.show');
                Route::post('/programs/{program}/update', 'updateProgram')->name('programs.update');
                Route::post('/programs/{program}/delete', 'deleteProgram')->name('programs.delete');
            });

            // {document} is resolved through Event::documents(); the controller only serves library items.
            Route::controller(LibraryController::class)->group(function () {
                Route::get('/library', 'index')->name('library');
                Route::post('/library/insert', 'store')->name('library.insert');
                Route::get('/library/{document}/download', 'download')->name('library.download');
                Route::post('/library/{document}/delete', 'delete')->name('library.delete');
            });

            // {comment} under a meeting resolves through the meeting's own comments.
            Route::controller(MeetingController::class)->group(function () {
                Route::get('/meetings', 'index')->name('meetings');
                Route::post('/meetings/insert', 'store')->name('meetings.insert');
                Route::get('/meetings/{meeting}', 'detail')->name('meetings.detail');
                Route::get('/meetings/{meeting}/show', 'show')->name('meetings.show');
                Route::post('/meetings/{meeting}/update', 'update')->name('meetings.update');
                Route::post('/meetings/{meeting}/delete', 'delete')->name('meetings.delete');
                Route::post('/meetings/{meeting}/minutes', 'saveMinutes')->name('meetings.minutes');
                Route::post('/meetings/{meeting}/attendance', 'markAttendance')->name('meetings.attendance');
                Route::get('/meetings/{meeting}/pdf', 'pdf')->name('meetings.pdf');
                Route::post('/meetings/{meeting}/comments/insert', 'postComment')->name('meetings.comments.insert');
                Route::post('/meetings/{meeting}/comments/{comment}/delete', 'deleteComment')->name('meetings.comments.delete');
            });

            // {dependency}, {document} and {comment} resolve through the task, so they must belong to it.
            Route::controller(TaskController::class)->group(function () {
                Route::post('/tasks/insert', 'store')->name('tasks.insert');
                Route::get('/tasks/{task}', 'detail')->name('tasks.detail');
                Route::get('/tasks/{task}/show', 'show')->name('tasks.show');
                Route::post('/tasks/{task}/update', 'update')->name('tasks.update');
                Route::post('/tasks/{task}/dependencies/insert', 'addDependency')->name('tasks.dependencies.insert');
                Route::post('/tasks/{task}/dependencies/{dependency}/delete', 'removeDependency')->name('tasks.dependencies.delete');
                Route::post('/tasks/{task}/complete', 'complete')->name('tasks.complete');
                Route::post('/tasks/{task}/result', 'saveResult')->name('tasks.result');
                Route::post('/tasks/{task}/comments/insert', 'postComment')->name('tasks.comments.insert');
                Route::post('/tasks/{task}/comments/{comment}/delete', 'deleteComment')->name('tasks.comments.delete');
                Route::post('/tasks/{task}/documents/insert', 'uploadDocument')->name('tasks.documents.insert');
                Route::get('/tasks/{task}/documents/{document}/download', 'downloadDocument')->name('tasks.documents.download');
                Route::post('/tasks/{task}/documents/{document}/delete', 'deleteDocument')->name('tasks.documents.delete');
            });
        });
    });
