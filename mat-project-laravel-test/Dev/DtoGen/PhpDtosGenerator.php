<?php

namespace Dev\DtoGen {

    use App\Utils\StrUtils;
    use App\Utils\Utils;
    use Illuminate\Support\Str;
    use Swaggest\JsonSchema\Context;
    use Swaggest\JsonSchema\JsonSchema;
    use Swaggest\JsonSchema\Schema;
    use Swaggest\PhpCodeBuilder\JsonSchema\ClassHookCallback;
    use Swaggest\PhpCodeBuilder\JsonSchema\PhpBuilder;
    use Swaggest\PhpCodeBuilder\PhpClass;
    use Swaggest\PhpCodeBuilder\PhpCode;
    use Swaggest\PhpCodeBuilder\PhpConstant;
    use Swaggest\PhpCodeBuilder\PhpFunction;
    use Swaggest\PhpCodeBuilder\PhpStdType;

    class PhpDtosGenerator
    {
        const DEFS = "defs";

        public static function generate(mixed $schemaData, string $rootName, string $basePath, string $baseNameSpace, string $relResolverDir = null, string $schemaFilePath = null, string $separator = DIRECTORY_SEPARATOR)
        {
            if ($schemaFilePath) {
                $schemaFilePath = realpath($schemaFilePath);
                if (!$schemaFilePath) {
                    $schemaFilePath = null;
                }
            }
            echo "\n!!Generating dtos... !!\n";
            $schemaBase = <<<'EOF'
            C:\Users\Jindra\source\repos\JS\Mat_project_backend-test\mat-project-laravel-test\schemas
            EOF;
            $relResolverDir ??= MyFileInfo::dirname($schemaFilePath);

            $remoteRefProvider = $relResolverDir ?
                new RelativeSchemaRefResolver($relResolverDir)
                : null;

            $schemaContext = new Context($remoteRefProvider);
            $swaggerSchema = Schema::import($schemaData, $schemaContext);

            $appPath = $basePath;
            $appPath = <<<'EOF'
            C:\Users\Jindra\source\repos\JS\Mat_project_backend-test\mat-project-laravel-test\app\Dtos
            EOF;
            echo "AppPath: " . $appPath . "\n";
            $appNs = MyFileInfo::omitAllExtensions($baseNameSpace);
            echo "AppNS: " . $appNs . "\n";
            $app = new MyApp();
            $app->setNamespaceRoot($appNs, '.');

            $builder = new PhpBuilder();
            $builder->namesFromDescriptions = true;
            $builder->buildSetters = true;
            $builder->makeEnumConstants = true;

            $rootName = Str::studly($rootName);
            echo "RelResolverDir: '$relResolverDir'\n";
            $quotedSeparator = null; {
                $builder->classCreatedHook = new ClassHookCallback(
                    function (PhpClass $class, string $path, JsonSchema $schema)
                    use (
                        $app,
                        $appPath,
                        $appNs,
                        $rootName,
                        $schemaFilePath,
                        $separator,
                        $quotedSeparator,
                        $relResolverDir,
                        $schemaBase,
                    ) {
                        /*
                   C:\Users\Jindra\source\repos\JS\Mat_project_backend-test\mat-project-laravel-test
                   \schemas\defs\exercises\FillInBlanks\review_response.json
                   ->properties:properties->additionalProperties:content
                   ->properties:items->anyOf[0]->properties:anyOf->items[1]:1
                   */
                        $fromRefs = $schema->getFromRefs();
                        echo "\n----------path: '$path'---------------\n";
                        $classSchemaFilePath = Str::before($path, '#');
                        if (!$classSchemaFilePath) {
                            if ($path === '#') {
                                $classSchemaFilePath = $schemaFilePath;
                            } else {

                                $docPath = $schema->getDocumentPath();
                                echo "DOCPATH: '$docPath'\n";
                                $refs = array_filter(
                                    explode('$ref:', $docPath),
                                    fn ($ref) => !Str::startsWith($ref, '#')
                                );

                                $lastDocPathFile = '';
                                $lastDocPath = Utils::lastArrayValue($refs);
                                if ($lastDocPath) {
                                    echo "Last doc path: '$lastDocPath'\n";
                                    $lastDocPathFile = Str::before(Str::before($lastDocPath, '->'), '#');
                                }

                                $classSchemaFilePath = $lastDocPathFile ?: $schemaFilePath;
                                // if($path === '#/$defs/group->resources->items'){
                                //     echo "----SHOW DOCPATH AND CLASS AND SCHEMA?\n";
                                // $show = fgetc(STDIN);
                                // if($show === 'y'){
                                //     dump([$docPath,$class,$schema]);
                                // }
                                // echo "---PRESS KEY TO CONTINUE?\n";
                                // fgetc(STDIN);
                                // }

                            }
                        }
                        echo "classSchemaFilePath: " . $classSchemaFilePath . "\n";
                        $desc = '';
                        if ($schema->title) {
                            $desc = $schema->title;
                        }
                        if ($schema->description) {
                            $desc .= "\n" . $schema->description;
                        }
                        if ($fromRefs = $schema->getFromRefs()) {
                            $desc .= "\nBuilt from " . implode("\n" . ' <- ', $fromRefs);
                        }

                        $class->setDescription(trim($desc));

                        $namespace = $class->getNamespace();
                        echo "classSchemaFilePath '$classSchemaFilePath' starts with '$schemaBase'\n";
                        if (Str::startsWith($classSchemaFilePath, $schemaBase)) {
                            echo "TRUE\n";

                            $namespace = implode('\\', array_map(fn ($part) => Str::studly($part), explode('\\', Str::replace(
                                search: ['/', '\\'],
                                replace: '\\',
                                subject: MyFileInfo::dirname(Str::replaceStart($schemaBase, '', $classSchemaFilePath))
                            ))));
                        }

                        $class->setNamespace(PathHelper::concatPaths($appNs, $namespace));
                        echo "Namespace: " . PathHelper::concatPaths($appNs, $namespace) . "\n";
                        if ('#' === $path) {
                            $class->setName($rootName); // Class name for root schema
                        } elseif (strpos($path, "#/" . PhpDtosGenerator::DEFS . "/") === 0) {
                            $class->setName(PhpCode::makePhpClassName(
                                substr($path, strlen("#/" . PhpDtosGenerator::DEFS . "/"))
                            ));
                        } else {
                            $className = $class->getName();
                            //     echo "Class " . $className."\n";
                            //     echo "Titles: " . $schema->title. "\n";
                            //     echo "Description: " .$schema->description."\n";
                            //    // var_dump($schema);
                            //     var_dump($class);
                            //     var_dump($path);
                            if ($schema->title) {
                                echo "TITLE\n";
                                $className = $schema->title;
                            } else if ($path && mb_strlen($className) > 15) {
                                //    echo "PATH " . $path."\n";
                                $path = Str::remove(['$', '[', ']', '(', ')'], $path);
                                $pathParts = preg_split("/->|#/u", $path);
                                if (!$pathParts) {
                                    $pathParts = [$path];
                                }
                                if ($schemaFilePath && !Str::startsWith($path, '#')) {
                                    $i = 0;
                                    $len = min(strlen($path), strlen($schemaFilePath));
                                    /** @noinspection PhpStatementHasEmptyBodyInspection */
                                    for (; $i < $len && $path[$i] === $schemaFilePath[$i]; ++$i);
                                    $pathParts[0] = substr($path, $i);
                                }
                                if (!$quotedSeparator) {
                                    $quotedSeparator = preg_quote($separator, '/');
                                }
                                $regex2 = "/$quotedSeparator/u";
                                $firstPathPart = MyFileInfo::omitAllExtensions(array_shift($pathParts));
                                $parts = [];
                                $splitted = preg_split($regex2, $firstPathPart);
                                if ($splitted) {
                                    array_push($parts, ...array_slice($splitted, -2));
                                }
                                foreach ($pathParts as $part) {
                                    $splitted = preg_split($regex2, $part);
                                    if ($splitted) {
                                        array_push($parts, ...$splitted);
                                    }
                                }
                                if ($parts) {
                                    $result = "";
                                    foreach ($parts as $group) {
                                        if ($group) {
                                            $result .= Str::studly($group);
                                        }
                                    }

                                    if ($result) {
                                        $className = $result;
                                    }
                                }
                            }
                            $class->setName(PhpCode::makePhpClassName(
                                $className
                            ));
                            echo "Class: " . $class->getFullyQualifiedName() . "\n";
                        }
                        $app->addClass($class);
                    }
                );
            }

            $functionNameReflect = new \ReflectionProperty(PhpFunction::class, 'name');
            /** @noinspection PhpExpressionResultUnusedInspection */
            $functionNameReflect->setAccessible(true);
            $functionArgumentsReflect = new \ReflectionProperty(PhpFunction::class, 'arguments');
            /** @noinspection PhpExpressionResultUnusedInspection */
            $functionArgumentsReflect->setAccessible(true);
            $classMethodsReflect = new \ReflectionProperty(PhpClass::class, 'methods');
            /** @noinspection PhpExpressionResultUnusedInspection */
            $classMethodsReflect->setAccessible(true);

            $builder->classPreparedHook = new ClassHookCallback(
                function (PhpClass $class, string $path, JsonSchema $schema)
                use (
                    $app,
                    $appPath,
                    $appNs,
                    $rootName,
                    $schemaFilePath,
                    $separator,
                    $quotedSeparator,
                    $relResolverDir,
                    $schemaBase,
                    $classMethodsReflect,
                    $functionNameReflect,
                    $functionArgumentsReflect) {
                    $createFuncBody = <<<'EOF'
                    $instance = parent::create();
                    EOF;

                    $constants = [];
                    $props = $schema->properties;
                    $required = $schema->required ?? [];
                    // transform required to map with camel case keys
                    {
                    $newRequired = [];
                    while(($req = array_shift($required)) !== null){
                        $newRequired[PhpCode::makePhpName($req)] = true;
                    }
                    $required = $newRequired;
                    unset($newRequired);
                }
                    echo 'required: ';
                    dump($required);
                    $propNames = [];
                    foreach ($props as $name => $value) {
                        $baseName = PhpCode::makePhpName($name);
                        $name = $baseName;
                        if (Utils::arrayHasKey($propNames, $baseName)) {
                            $name .= ++$propNames[$baseName];
                        } else {
                            $propNames[$baseName] = 1;
                        }
                        $class->addConstant(new PhpConstant(Str::upper(Str::snake($name)), $name));
                        $isConstant = isset($value->const);
                        $const = $value->const;
                        if ($isConstant) {
                            $constants[$name] = $const;
                            if (Utils::arrayHasKey($required,$name)) {
                                $const = var_export($const,true);
                                $createFuncBody .= "\n\$instance->$name = $const;";
                            }
                        }
                    }
                    /**
                     * @var array<string|int,mixed> $constants
                     */


                    if ($constants) {
                        echo "constants: ";
                        dump($constants);
                        $createFuncBody .= "\nreturn \$instance;";
                        $createFunc = new PhpFunction(name: 'create', visibility: 'public', isStatic: true);
                        $createFunc->setResult(PhpStdType::tStatic())
                            ->setBody($createFuncBody);

                        $class->addMethod($createFunc);

                        $methods =  $classMethodsReflect->getValue($class);
                        echo "methods: ";
                        dump(count($methods));
                        for($i = count($methods)-1;$i>=0;--$i){
                            $method = $methods[$i];

                            $name = $functionNameReflect->getValue($method);
                            echo "name: " . $name."\n";
                            if (Str::startsWith($name, 'set')) {
                                $nameAfterSet = Str::lcfirst(substr($name, strlen('set')));
                                echo "nameAfterSet: " . $nameAfterSet . "\n";
                                if(Utils::arrayHasKey($constants, $nameAfterSet)){
                                    if(Utils::arrayHasKey($required,$nameAfterSet)){
                                        unset($methods[$i]);
                                    }
                                    else{
                                        $value = $constants[$nameAfterSet];
                                        $value = var_export($value,true);
                                        echo "Constant '$nameAfterSet' = '$value'\n";

                                        $methods[$i]->setBody(
                                            '$this->'.$nameAfterSet.' = '.$value.';'
                                            ."\n".'return $this;'
                                        );
                                        $functionArgumentsReflect->setValue($methods[$i],[]);
                                    }
                                }
                            }
                        }
                        $classMethodsReflect->setValue($class, array_values($methods));
                    }
                }
            );

            $builder->getType($swaggerSchema);
            // $app->clearOldFiles($appPath);
            $app->storeNoClear($appPath);
        }
    }
}
