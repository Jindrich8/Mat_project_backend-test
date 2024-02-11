<?php

namespace App\Providers;

use App\Types\DBCascadeType;
use App\Utils\DBUtils;
use Illuminate\Database\Query\Grammars\Grammar;
use Illuminate\Database\Schema\Blueprint;
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

    Blueprint::macro('pgEnum', function (string $enumName, string $columnName) {
      /**
       * @var Blueprint $this
       */
      $this->addColumn('raw', $columnName, ['raw_type' => DBUtils::getPGEnumTypeName($enumName)]);
    });

    Blueprint::macro('fixedFloat4', function (string $columnName) {
      /**
       * @var Blueprint $this
       */
      $this->addColumn('raw', $columnName, ['raw_type' => 'FLOAT(24)']);
    });

    Blueprint::macro(
      'pkFKColumn',
      /**
       * @param string $keyName Name of PK FK column
       * @param string $references PK key that is referenced by this PK FK key
       * @param string $onTable Table on which is referenced key located
       * @param \App\Types\DBCascadeType|null $cascadeType Whether to cascade on delete or update or never
       * Creates a PK FK column that references specified PK column on specified table
       */
      function (string $keyName, string $references, string $onTable, DBCascadeType|null $cascadeType = null) {
        /**
         * @var Blueprint $this
         */
        $this->unsignedBigInteger($keyName)->primary();
        $foreign =  $this->foreign($keyName)->references($references)->on($onTable);
        if ($cascadeType === DBCascadeType::DELETE) {
          $foreign->cascadeOnDelete();
        } else if ($cascadeType === DBCascadeType::UPDATE) {
          $foreign->cascadeOnUpdate();
        }
      }
    );
  }
}
