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
            $quotedSeparator = null;
          

            {
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
                    $classSchemaFilePath = Str::before($path, '#');
                    if (!$classSchemaFilePath) {
                        $classSchemaFilePath = $schemaFilePath;
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
                            $regex2 = <<< END
/$quotedSeparator/u
END;
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
        $functionNameReflect->setAccessible(true);
        $classMethodsReflect = new \ReflectionProperty(PhpClass::class, 'methods');
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
                    $functionNameReflect
                ) {
                    $createFuncBody = <<<'EOF'
                    $instance = parent::create();
                    EOF;

                        $constants = [];
                        $props = $schema->properties;
                        $propNames = [];
                        foreach ($props as $name => $value) {
                            $baseName = PhpCode::makePhpName($name);
                            $name = $baseName;
                            if(Utils::arrayHasKey($propNames,$baseName)){
                               $name .= ++$propNames[$baseName];
                            }
                            else{
                                $propNames[$baseName] = 1;
                            }
                            $class->addConstant(new PhpConstant(Str::upper(Str::snake($name)), $name));
                            $isConstant = isset($value->const);
                            $const = $value->const;
                            if ($isConstant) {
                                $constants[$name] = $const;
                                if (!is_numeric($const)) {
                                    $const = "\"$const\"";
                                }
                                $createFuncBody .= "\n\$instance->$name = $const;";
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
                            /** @var PhpFunction[] */
                            $methods =  $classMethodsReflect->getValue($class);
                            $methods = array_filter($methods, function ($method) use ($functionNameReflect, &$constants) {
                                $name = $functionNameReflect->getValue($method);
                                if (Str::startsWith($name, 'set')) {
                                    $nameAfterSet = Str::lcfirst(substr($name, strlen('set')));
                                    echo "nameAfterSet: " . $nameAfterSet."\n";
                                    return !Utils::arrayHasKey($constants, $nameAfterSet);
                                }
                                return true;
                            });
                            $classMethodsReflect->setValue($class, $methods);
                        }
                });

            $builder->getType($swaggerSchema);
            // $app->clearOldFiles($appPath);
            $app->storeNoClear($appPath);
        }
    }
}
