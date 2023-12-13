php schema_gen.php --dir=./schemas --sep=/ --outSep=/ --force
php dto_gen.php --dir=./schemas --sep=/ --excludeRelDir=/defs/ --targetDir="./app/Dtos" --targetNamespace="App\Dtos" --force