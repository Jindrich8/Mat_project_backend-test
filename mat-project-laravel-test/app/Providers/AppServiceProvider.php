<?php

namespace App\Providers;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
        Blueprint::macro('autoTimestamps',
        /**
         * Creates a timestamps, that use the current timestamp as defaut value
         */
        function(){
            /**
             * @var Blueprint $this
             */
            $this->timestamp('created_at')->useCurrent();
            $this->timestamp('updated_at')->nullable()
            ->useCurrentOnUpdate()
            ->default(null);
        });

        Blueprint::macro('pkFKColumn',
        /**
         * @param string $keyName Name of PK FK column
         * @param string $references PK key that is referenced by this PK FK key
         * @param string $onTable Table on which is referenced key located
         * Creates a PK FK column that references specified PK column on specified table
         */
        function(string $keyName,string $references,string $onTable){
               /**
             * @var Blueprint $this
             */
            $this->unsignedBigInteger($keyName)->primary();
            $this->foreign($keyName)->references($references)->on($onTable);
        });
    }
}
