<?php

namespace Database\Seeders;

use App\Dtos\Defs\Endpoints\Task\Create\CreateRequestTask;
use App\Dtos\Defs\Endpoints\Task\Create\TaskCreateRequest;
use App\Dtos\Defs\Types\Request\RequestOrderedEnumRange;
use App\Exceptions\ApplicationException;
use App\Helpers\Database\UserHelper;
use App\Http\Controllers\TaskController;
use App\Models\Tag;
use App\Models\User;
use App\TableSpecificData\TaskClass;
use App\TableSpecificData\TaskDifficulty;
use App\TableSpecificData\UserRole;
use App\Types\EasyMessageLogger;
use App\Types\LogLogger;
use App\Types\SimpleAuthProvider;
use App\Utils\DebugLogger;
use App\Utils\DtoUtils;
use Illuminate\Database\Seeder;
use Illuminate\Http\Request;
use Illuminate\Support\Stringable as SupportStringable;
use Psr\Log\LoggerInterface;
use Str;
use Swaggest\JsonSchema\Structure\ClassStructure;

class TaskSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * @throws ApplicationException
     */
    public function run(): void
    {

        $sources = [
            //#region SOURCES
            <<<EOF
                                        <document>
                                <description>
                                Doc description
                            </description>
                            <entries>
                            <group>
                            <resources>
                                <resource>
                                    Resources
                                </resource>
                            </resources>
                            <members>
                                <exercise type="FillInBlanks" weight="12">
                                    <instructions>Prostě doplňovačka</instructions>
                                    <content>
                                    Čern[ý/í] rybíz je moc dobrý. H[i/y]erogl[y/i]f[y/i] jsou těžké.
                                    </content>
                                </exercise>
                                <exercise type="FixErrors" weight="17">
                                    <instructions>Prostě opravování chyb</instructions>
                                    <content>
                                        <correctText>
                                    Ranní světlo proniklo skrze závěsy.
                                    </correctText>
                                    <text>
                                        Raní svjetlo proniklo zkrze závěsy.
                                    </text>
                                    </content>
                                </exercise>
                            </members>
                            </group>
                            <exercise type="FillInBlanks" weight="12">
                                                <instructions>Prostě doplňovačka</instructions>
                                                <content>
                                                    Čern[ý/í] rybíz je moc dobrý. H[i/y]erogl[y/i]f[y/i] jsou těžké. H[i/y]erogl[y/i]f[y/i] jsou těžké.
                                                Čern[ý/í] rybíz je moc dobrý. H[i/y]erogl[y/i]f[y/i] jsou těžké. H[i/y]erogl[y/i]f[y/i] jsou těžké.
                                                Čern[ý/í] rybíz je moc dobrý. H[i/y]erogl[y/i]f[y/i] jsou těžké.
                                                Čern[ý/í] rybíz je moc dobrý. H[i/y]erogl[y/i]f[y/i] jsou těžké. H[i/y]erogl[y/i]f[y/i] jsou těžké.

                                                    Čern[ý/í] rybíz je moc dobrý. H[i/y]erogl[y/i]f[y/i] jsou těžké.
                                                Čern[ý/í] rybíz je moc dobrý. H[i/y]erogl[y/i]f[y/i] jsou těžké. H[i/y]erogl[y/i]f[y/i] jsou těžké.
                                                Čern[ý/í] rybíz je moc dobrý. H[i/y]erogl[y/i]f[y/i] jsou těžké.
                                                </content>
                                            </exercise>
                            </entries>
                            </document>
                    EOF,
            <<<EOF
                                            <document>
                                    <description>
                                        Doc description
                                    </description>
                                    <entries>
                                    <exercise type="FillInBlanks" weight="12">
                                                    <instructions>Prostě doplňovačka</instructions>
                                                    <content>
                                                        Čern[ý/í] rybíz je moc dobrý. H[i/y]erogl[y/i]f[y/i] jsou těžké. H[i/y]erogl[y/i]f[y/i] jsou těžké.
                                                    Čern[ý/í] rybíz je moc dobrý. H[i/y]erogl[y/i]f[y/i] jsou těžké. H[i/y]erogl[y/i]f[y/i] jsou těžké.
                                                    Čern[ý/í] rybíz je moc dobrý. H[i/y]erogl[y/i]f[y/i] jsou těžké.
                                                    Čern[ý/í] rybíz je moc dobrý. H[i/y]erogl[y/i]f[y/i] jsou těžké. H[i/y]erogl[y/i]f[y/i] jsou těžké.

                                                        Čern[ý/í] rybíz je moc dobrý. H[i/y]erogl[y/i]f[y/i] jsou těžké.
                                                    Čern[ý/í] rybíz je moc dobrý. H[i/y]erogl[y/i]f[y/i] jsou těžké. H[i/y]erogl[y/i]f[y/i] jsou těžké.
                                                    Čern[ý/í] rybíz je moc dobrý. H[i/y]erogl[y/i]f[y/i] jsou těžké.
                                                    </content>
                                                </exercise>
                                        <group>
                                            <resources>
                                                <resource>
                                                    Resources
                                                </resource>
                                            </resources>
                                            <members>
                                                <exercise type="FillInBlanks" weight="12">
                                                    <instructions>Prostě doplňovačka</instructions>
                                                    <content>
                                                        Čern[ý/í] rybíz je moc dobrý. H[i/y]erogl[y/i]f[y/i] jsou těžké. H[i/y]erogl[y/i]f[y/i] jsou těžké.
                                                    Čern[ý/í] rybíz je moc dobrý. H[i/y]erogl[y/i]f[y/i] jsou těžké. H[i/y]erogl[y/i]f[y/i] jsou těžké.
                                                    Čern[ý/í] rybíz je moc dobrý. H[i/y]erogl[y/i]f[y/i] jsou těžké. H[i/y]erogl[y/i]f[y/i] jsou těžké.

                                                        Čern[ý/í] rybíz je moc dobrý. H[i/y]erogl[y/i]f[y/i] jsou těžké. H[i/y]erogl[y/i]f[y/i] jsou těžké.
                                                    Čern[ý/í] rybíz je moc dobrý. H[i/y]erogl[y/i]f[y/i] jsou těžké. H[i/y]erogl[y/i]f[y/i] jsou těžké.
                                                    Čern[ý/í] rybíz je moc dobrý. H[i/y]erogl[y/i]f[y/i] jsou těžké. H[i/y]erogl[y/i]f[y/i] jsou těžké.
                                                    </content>
                                                </exercise>
                                                <exercise type="FixErrors" weight="17">
                                                    <instructions>Prostě opravování chyb</instructions>
                                                    <content>
                                                        <correctText>
                                                    Ranní světlo proniklo skrze závěsy.
                                                    </correctText>
                                                    <text>
                                                        Raní svjetlo proniklo zkrze závěsy.
                                                    </text>
                                                    </content>
                                                </exercise>
                                            </members>
                                        </group>
                                    </entries>
                                </document>
                            EOF,
            <<<EOF
                            <document>
                    <description>
                        Doc description
                    </description>
                    <entries>
                    <exercise type="FillInBlanks" weight="12">
                                    <instructions>Prostě doplňovačka</instructions>
                                    <content>
                                        Čern[ý/í] rybíz je moc dobrý. H[i/y]erogl[y/i]f[y/i] jsou těžké. H[i/y]erogl[y/i]f[y/i] jsou těžké.
                                    Čern[ý/í] rybíz je moc dobrý. H[i/y]erogl[y/i]f[y/i] jsou těžké. H[i/y]erogl[y/i]f[y/i] jsou těžké.
                                    Čern[ý/í] rybíz je moc dobrý. H[i/y]erogl[y/i]f[y/i] jsou těžké.
                                    Čern[ý/í] rybíz je moc dobrý. H[i/y]erogl[y/i]f[y/i] jsou těžké. H[i/y]erogl[y/i]f[y/i] jsou těžké.

                                        Čern[ý/í] rybíz je moc dobrý. H[i/y]erogl[y/i]f[y/i] jsou těžké.
                                    Čern[ý/í] rybíz je moc dobrý. H[i/y]erogl[y/i]f[y/i] jsou těžké. H[i/y]erogl[y/i]f[y/i] jsou těžké.
                                    Čern[ý/í] rybíz je moc dobrý. H[i/y]erogl[y/i]f[y/i] jsou těžké.
                                    </content>
                                </exercise>
                        <group>
                            <resources>
                                <resource>
                                    Resources
                                </resource>
                            </resources>
                            <members>
                                <exercise type="FillInBlanks" weight="12">
                                    <instructions>Prostě doplňovačka</instructions>
                                    <content>
                                        Čern[ý/í] rybíz je moc dobrý. H[i/y]erogl[y/i]f[y/i] jsou těžké. H[i/y]erogl[y/i]f[y/i] jsou těžké.
                                    Čern[ý/í] rybíz je moc dobrý. H[i/y]erogl[y/i]f[y/i] jsou těžké. H[i/y]erogl[y/i]f[y/i] jsou těžké.
                                    Čern[ý/í] rybíz je moc dobrý. H[i/y]erogl[y/i]f[y/i] jsou těžké.
                                    Čern[ý/í] rybíz je moc dobrý. H[i/y]erogl[y/i]f[y/i] jsou těžké. H[i/y]erogl[y/i]f[y/i] jsou těžké.

                                        Čern[ý/í] rybíz je moc dobrý. H[i/y]erogl[y/i]f[y/i] jsou těžké.
                                    Čern[ý/í] rybíz je moc dobrý. H[i/y]erogl[y/i]f[y/i] jsou těžké. H[i/y]erogl[y/i]f[y/i] jsou těžké.
                                    Čern[ý/í] rybíz je moc dobrý. H[i/y]erogl[y/i]f[y/i] jsou těžké.
                                    </content>
                                </exercise>
                                <exercise type="FixErrors" weight="17">
                                    <instructions>Prostě opravování chyb</instructions>
                                    <content>
                                        <correctText>
                                    Ranní světlo proniklo skrze závěsy. Černočerná ovce běhá po stráni a bečí, že tráva byla kdysi zelenější.
                                    Slunce je hvězda v naší sluneční soustavě, ale není to jediná hvězda, kterou známe.
                                    Tráva je tráva, kráva je kráva.
                                    </correctText>
                                    <text>
                                    Ranní světlo proniklo skrze závěsy. Černocerná ovce bjehá po stráni a becí, že tráva byla kdysi zelenějsí.
                                    Slunce je hvjezda v naší sluneční soustavě, ale není to jediná hvjezda, kterou známe.
                                    Tráva je tráva, kráva je kráva.
                                    </text>
                                    </content>
                                </exercise>
                                <exercise type="FillInBlanks" weight="12">
                                <instructions>Prostě doplňovačka</instructions>
                                <content>
                                    Čern[ý/í] rybíz je moc dobrý. H[i/y]erogl[y/i]f[y/i] jsou těžké. H[i/y]erogl[y/i]f[y/i] jsou těžké.
                                Čern[ý/í] rybíz je moc dobrý. H[i/y]erogl[y/i]f[y/i] jsou těžké. H[i/y]erogl[y/i]f[y/i] jsou těžké.
                                Čern[ý/í] rybíz je moc dobrý. H[i/y]erogl[y/i]f[y/i] jsou těžké.
                                Čern[ý/í] rybíz je moc dobrý. H[i/y]erogl[y/i]f[y/i] jsou těžké. H[i/y]erogl[y/i]f[y/i] jsou těžké.

                                    Čern[ý/í] rybíz je moc dobrý. H[i/y]erogl[y/i]f[y/i] jsou těžké.
                                Čern[ý/í] rybíz je moc dobrý. H[i/y]erogl[y/i]f[y/i] jsou těžké. H[i/y]erogl[y/i]f[y/i] jsou těžké.
                                Čern[ý/í] rybíz je moc dobrý. H[i/y]erogl[y/i]f[y/i] jsou těžké.
                                </content>
                            </exercise>
                                <exercise type="FixErrors" weight="27">
                                    <instructions>Prostě opravování chyb</instructions>
                                    <content>
                                        <correctText>
                                    Ranní světlo proniklo skrze závěsy. Černočerná ovce běhá po stráni a bečí, že tráva byla kdysi zelenější.
                                    Slunce je hvězda v naší sluneční soustavě, ale není to jediná hvězda, kterou známe.
                                    Tráva je tráva, kráva je kráva.
                                    </correctText>
                                    <text>
                                    Ranní světlo proniklo skrze závěsy. Černocerná ovce bjehá po stráni a becí, že tráva byla kdysi zelenějsí.
                                    Slunce je hvjezda v naší sluneční soustavě, ale není to jediná hvjezda, kterou známe.
                                    Tráva je tráva, kráva je kráva.
                                    </text>
                                    </content>
                                </exercise>
                            </members>
                        </group>
                    </entries>
                </document>
            EOF
            //#endregion SOURCES
        ];

        $sourceCount = count($sources);


        $createRequest = function (array|object|string $requestData, string $uri = 'bla/bla', string $method = 'POST'): Request {
            if ($requestData instanceof ClassStructure) {
                $requestData = ['data' => DtoUtils::exportDto($requestData)];
            }
            $content = null;
            if ($method !== 'GET') {
                $content = DtoUtils::exportedDtoToJson($requestData);
            } else {
                $uri .= "?" . http_build_query($requestData);
            }
            $request = Request::create(uri: $uri, method: $method, content: $content);
            // $request->setJson(json_encode(['data'=>['data'=>$requestData]]));
            $request->headers->add(['Accept' => 'application/json', 'CONTENT_TYPE' => '/json']);

            //    $validated = $request->validate(['data'=>'required']);
            //    echo "VALIDATED: ";
            //    dump($validated);
            return $request;
        };
        /**
         * @extends EasyMessageLogger<LoggerInterface,string>
         */
        $logger = new class extends EasyMessageLogger
        {
            private LogLogger $logger;

            public function __construct(){
                $this->logger = new LogLogger();
            }

            protected function getChannel(mixed $channel): mixed
            {
                return $this->logger->channel($channel);
            }
            
            public function logToChannelWContext($level, string|SupportStringable $message, array $context = [], mixed $channel = null): void
            {
                if ($level <= LOG_ERR || $channel === 'performance') {
                    $this->logger->logToChannel($level, $message, $context,$channel);
                }
            }
        };
        DebugLogger::withLogger($logger, function () use ($sourceCount, $sources, $createRequest) {
            UserHelper::withAuthProvider(
                new SimpleAuthProvider(User::whereRole(UserRole::TEACHER->value)->get()->random()),
                function () use ($sourceCount, $sources, $createRequest) {
                    $classes = TaskClass::cases();
                    $tags = Tag::all();
                    $classCount = count($classes);
                    $tagCount = count($tags);

                    for ($i = 0; $i < 300; ++$i) {
                        DebugLogger::log("!!!!---------------INSERTING TASK ---------- - SEEEDING TASK -----------------!!!!!!");
                        $minClass = rand(0, $classCount - 1);
                        $maxClass = rand($minClass, $classCount - 1);

                        $randomTags = collect($tags->random(rand(1, $tagCount - 1)))->map(fn (Tag $tag) => $tag->id . '')
                            ->all();
                        $sourceI = rand(0, $sourceCount - 1);
                        $source = $sources[$sourceI];
                        $unique = fake()->unique();
                        $name = $unique->userName();
                        $nameLen = 0;
                        while(($nameLen = Str::length($name,'UTF-8')) < 5){
                            $name.=$unique->userName();
                        }
                        if($nameLen >= 50){
                        $name = Str::substr($name,0,49);
                        }
                        $request =  $createRequest(
                            TaskCreateRequest::create()
                                ->setTask(
                                    CreateRequestTask::create()
                                    ->setName($name)
                                    ->setDisplay(
                                        rand(0,1) === 1 ? 
                                    CreateRequestTask::HORIZONTAL 
                                    : CreateRequestTask::VERTICAL
                                    )
                                        ->setClassRange(
                                            RequestOrderedEnumRange::create()
                                                ->setMin($classes[$minClass]->value)
                                                ->setMax($classes[$maxClass]->value)
                                        )
                                        ->setDifficulty(TaskDifficulty::MEDIUM->value)
                                        ->setIsPublic(true)
                                        ->setTags($randomTags)
                                        ->setSource($source)
                                )
                        );

                        TaskController::construct()->store(
                            $request
                        );
                    }
                }
            );
        },dump:false);


        //
    }
}
