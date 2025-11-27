<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use App\Models\KasirSesi;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Paginator::useBootstrap();

        View::composer('layouts.main', function ($view) {

            if (Auth::check()) {
                $roleId = Auth::user()->role_id;

                $sesiKasirAktif = KasirSesi::where('status', 'BUKA')
                    ->whereHas('userPembuka', function ($query) use ($roleId) {
                        $query->where('role_id', $roleId);
                    })
                    ->first();

                $view->with('sesiKasirAktif', $sesiKasirAktif);
            } else {
                $view->with('sesiKasirAktif', null);
            }
        });
    }
}
