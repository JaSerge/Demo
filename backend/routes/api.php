<?php


use App\Http\Controllers\FilterHistoryController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\FilterController;
use App\Http\Controllers\UnavailabilityPeriodsController;
use App\Http\Controllers\HomeController;

use App\Http\Controllers\UserFtpController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'auth'], function () {

    Route::post('login', [AuthController::class, "login"])->block();
    Route::post('intlogin', [AuthController::class, "internalLogin"]);
    Route::get('get_captcha', [AuthController::class, 'getCaptcha'])->block();

    Route::post('logout', [AuthController::class, "logout"])->block();

    Route::get('checkApiAccess', [AuthController::class, "checkApiAccess"]);

    Route::group(['middleware' => 'authenticated'], function () {

        Route::post('logout/other', [AuthController::class, "logoutOtherDevices"]);
    });

    Route::post('password-recovery/send-email', [AuthController::class, 'sendRecoveryLink'])
        ->middleware('prepare.recover.triggers');
    Route::post('password-recovery/check', [AuthController::class, 'checkRecoveryToken'])
        ->middleware('prepare.recover.triggers');
    Route::post('password-recovery/password', [AuthController::class, 'recoverPassword'])
        ->middleware('prepare.recover.triggers');
});


Route::group(['middleware' => ['authenticated', 'prepare.triggers']], function () {

    // Профиль
    Route::group(['prefix' => 'profile'], function () {
        Route::put('/', [ProfileController::class, "updated"]);

        //Информация о себе
        Route::get('/', [ProfileController::class, "getSelf"])
            ->middleware('set.locale.cookie');

        Route::put("/change-password", [ProfileController::class, "changePassword"]);
    });

    // Профиль
    Route::group(['prefix' => 'sessions'], function () {

        Route::get('/own', [SessionController::class, "getOwnSessions"]);

        Route::post('/terminate-other', [SessionController::class, "terminateOther"]);

        Route::post('/{id}/terminate', [SessionController::class, "terminate"]);
    });

    // маршруты только для не заблокированных юзеров
    Route::group(['middleware' => 'blocked'], function () {
        Route::group(['prefix' => 'user_blocks', 'middleware' => ['permission:user_blocks_view']], function () {
            Route::get('/', [UserBlocksController::class, 'paginate']);
            Route::get('/block_types', [UserBlocksController::class, 'getBlockTypes']);
            Route::get('/users', [UserBlocksController::class, 'getUsers']);
            Route::delete('/{id}', [UserBlocksController::class, "delete"])
                ->where('id', '[0-9]+')
                ->middleware(['permission:user_blocks_delete']);
        });

        Route::group(['prefix' => 'filter_history'], function () {
            Route::get('/', [FilterHistoryController::class, "get"]);
            Route::post('/', [FilterHistoryController::class, "add"]);
        });

        // Специализированные endpoints для фильтров. Чтобы не было проблем с привелегиями.
        // Должны присутствовать кастомные проверки
        Route::group(['prefix' => 'filters'], function () {
            Route::get('/', [FilterController::class, "get"]);
            Route::post('/', [FilterController::class, "create"]);
            Route::post('/{id}/favorite', [FilterController::class, "setFavorite"])->where('id', '[0-9]+');
            Route::delete('/{id}', [FilterController::class, "delete"])->where('id', '[0-9]+');
            Route::post('/unfavorite', [FilterController::class, "deleteFavorite"]);
            Route::get('/users', [FilterController::class, "getAllUsersForFilter"]);
            Route::get('/roles', [FilterController::class, 'getRoles']);
            Route::get('/groups', [FilterController::class, "getAllGroupsForFilter"]);
        });

        // Здесь можно получить настройки системы, например конфиги для списков
        Route::group(['prefix' => 'settings'], function () {
            Route::get('/validation', [SettingController::class, "getValidationConfigs"]);
            Route::get('/unavailability-periods', [SettingController::class, "getUnavailabilityPeriodsSettings"]);
            Route::get('/versions', [SettingController::class, "getVersionSettings"]);
        });

        // Главная
        Route::prefix('home-workplace')->group(function () {            
            Route::get('/dashboard-tabs', [HomeController::class, "getTabs"])->middleware(['permission:home-workplace_view']);
            Route::get('/dashboard-operator_data/{operator_id}', [HomeController::class, "getOperatorData"])->middleware(['permission:home-workplace_view']);
            Route::get('/dashboard-data', [HomeController::class, "getData"])->middleware(['permission:home-workplace_view']);
            Route::get('/dashboard-process/{operator_id}', [HomeController::class, 'getProcess'])->middleware(['permission:home-workplace_view']);            
            Route::get('/dashboard-msisdn_ports/{period}', [HomeController::class, 'getMsisdnPorts'])->middleware(['permission:home-workplace_view']);
            Route::get('/dashboard-change_msisdns/{period}', [HomeController::class, 'getChangeMsisdns'])->middleware(['permission:home-workplace_view']);
            Route::get('/dashboard-avg_ports/{period}', [HomeController::class, 'getAvgPorts'])->middleware(['permission:home-workplace_view']);
            Route::get('/dashboard-success_processes/{period}', [HomeController::class, 'getSuccessProcesses'])->middleware(['permission:home-workplace_view']);            
        });

        //Навигация
        Route::group(['prefix' => 'navs'], function () {
            Route::get('/', [NavController::class, "getAllowedNavs"]);
        });       

        // Технические работы (Периоды недоступности)
        Route::prefix('unavailability-periods')->group(function () {
            Route::get('/', [UnavailabilityPeriodsController::class, 'getUnavailabilityPeriods'])->middleware(['permission:unavailability-periods_view']);
            Route::get('/available-operators', [UnavailabilityPeriodsController::class, 'getAvailableOperators'])->middleware(['permission:unavailability-periods_view']);
            Route::post('/', [UnavailabilityPeriodsController::class, 'create'])->middleware(['permission:unavailability-periods_add']);
            Route::put('/{id}', [UnavailabilityPeriodsController::class, "update"])->where('id', '[0-9]+')->middleware(['permission:unavailability-periods_edit']);
            Route::post('/{id}/close', [UnavailabilityPeriodsController::class, 'close'])->where('id', '[0-9]+')->middleware(['permission:unavailability-periods_delete']);    
            Route::get('/{id}', [UnavailabilityPeriodsController::class, "getUnPeriod"])->where('id', '[0-9]+');    
        });

        //История действий
        Route::group(['prefix' => 'action-log'], function () {

            Route::get('/', [LogController::class, "paginateLog"])
                ->middleware(['permission:action_log_view']);

            Route::get('/actions', [LogController::class, "getActions"])
                ->middleware(['permission:action_log_view']);

            Route::get('/models', [LogController::class, "getModels"])
                ->middleware(['permission:action_log_view']);

            Route::get('/workplaces', [LogController::class, "getWorkplaces"])
                ->middleware(['permission:action_log_view']);


            Route::get('/{name}/{id}', [LogController::class, "paginateObjectLogs"])
                ->where([
                    'name' => '[a-zA-Z\-\_]+',
                ])
                ->middleware(['permission:action_log_view']);
        });
    });
});
