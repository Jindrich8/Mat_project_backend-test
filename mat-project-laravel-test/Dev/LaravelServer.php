<?php
namespace Dev;

use App\Console\Kernel;
use Symfony\Component\Console\Input\ArgvInput;

class LaravelServer
{
    private static ?Kernel $kernel = null;
    private static int $status = 0;
    private static ?ArgvInput $input = null;

    public static function execute(callable $call): void
    {
        $kernel = self::getKernel();
        $call();
    }

    public static function dispose(): void
    {
        self::getKernel()->terminate(self::$input, self::$status);
    }

    private static function getKernel(): ?Kernel
    {
        if (!self::$kernel) {
            $app = require_once __DIR__ . '/../bootstrap/app.php';

            /*
|--------------------------------------------------------------------------
| Run The Artisan Application
|--------------------------------------------------------------------------
|
| When we run the console application, the current CLI command will be
| executed in this console and the response sent back to a terminal
| or another output device for the developers. Here goes nothing!
|
*/

            /**
             * @var \Illuminate\Contracts\Console\Kernel $kernel
             */
            self::$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);

            self::$status = self::$kernel->handle(
                self::$input = new \Symfony\Component\Console\Input\ArgvInput,
                new \Symfony\Component\Console\Output\ConsoleOutput
            );
        }
        return self::$kernel;
    }
}