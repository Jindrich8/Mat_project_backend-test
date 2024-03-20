<?php

namespace App\Providers;

use App\Types\DBCascadeTypeEnum;
use App\Utils\DBUtils;
use App\Utils\DebugLogger;
use Illuminate\Database\Query\Grammars\Grammar;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Fluent;
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
  //   DB::listen(function ($query) {
  //     DebugLogger::performance($query->time,'ms',"QUERY",['query' => $query->sql]);
  // });

    //
    Blueprint::macro(
      'autoTimestamps',
      /**
       * Creates a timestamps, that use the current timestamp as defaut value
       */
      function () {
        /**
         * @var Blueprint $this
         */
        $this->timestamp('created_at')->useCurrent();
        $this->timestamp('updated_at')->nullable()
          ->useCurrentOnUpdate()
          ->default(null);
      }
    );

    Grammar::macro('typeRaw', function (Fluent $column) {
      return $column->get('raw_type');
    });

    Blueprint::macro('fixedFloat4', function (string $columnName) {
      /**
       * @var Blueprint $this
       */
      $this->addColumn('raw', $columnName, ['raw_type' => 'FLOAT(23)']);
    });

    Blueprint::macro(
      'pkFKColumn',
      /**
       * @param string $keyName Name of PK FK column
       * @param string $references PK key that is referenced by this PK FK key
       * @param string $onTable Table on which is referenced key located
       * @param DBCascadeTypeEnum|null $cascadeType Whether to cascade on delete or update or never
       * Creates a PK FK column that references specified PK column on specified table
       */
      function (string $keyName, string $references, string $onTable, DBCascadeTypeEnum|null $cascadeType = null) {
        /**
         * @var Blueprint $this
         */
        $this->unsignedBigInteger($keyName)->primary();
        $foreign =  $this->foreign($keyName)->references($references)->on($onTable);
        if ($cascadeType === DBCascadeTypeEnum::DELETE) {
          $foreign->cascadeOnDelete();
        } else if ($cascadeType === DBCascadeTypeEnum::UPDATE) {
          $foreign->cascadeOnUpdate();
        }
      }
    );
  }
}
