<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;
use Illuminate\Support\Facades\Config;

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
        // $this->registerPolicies();

        Passport::routes();
    
        Passport::tokensCan([
            'oauth_access_tokens' => 'Access oauth_access_tokens scope',
            'patient' => 'Access patient scope',
        ]);
        
         Config::set(['admin_assets' => ['default-image-url' => 'https://staging-ayushman-patient.hexprojects.in/assets/uploads/default_image/logo-quare.png','doctor-image-path' => 'https://staging-ayushman-admin.hexprojects.in/assets/uploads/doctor_profile/','no-image-path'=>'https://staging-ayushman-admin.hexprojects.in/assets/images/','membership-packages-image-path' => 'https://staging-ayushman-admin.hexprojects.in/assets/uploads/membership_packages/','wellness-image-path' => 'https://staging-ayushman-admin.hexprojects.in/assets/uploads/wellness_image/']]);
         Config::set(['patient_assets' => ['default-image-url' => 'https://staging-ayushman-patient.hexprojects.in/assets/uploads/default_image/logo-quare.png']]);
    }
}
