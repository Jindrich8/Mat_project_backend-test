php schema_gen.php `
  --dir=./schemas `
   --sep=/ `
     --schemaNamePattern="/.special.json/" `
      --force
$CONTINUE = $LASTEXITCODE -eq 0
if (!$CONTINUE) {
  echo "`n--------------ERROR: $LASTEXITCODE ENCAUNTERED WHILE GENERATING SCHEMAS--------------`n"
  $reply = Read-Host -Prompt "Continue?[y/n]"
  $CONTINUE = $reply -eq 'y'
}

if($CONTINUE){
php schema_prop_replace.php "./errors/errors.json"
  $CONTINUE = $LASTEXITCODE -eq 0
  if(!$CONTINUE){
    echo "`n--------------ERROR: $LASTEXITCODE ENCAUNTERED WHILE REPLACING CODES IN ERRORS--------------`n"
  $reply = Read-Host -Prompt "Continue?[y/n]"
  $CONTINUE = $reply -eq 'y'
  }
}

if($CONTINUE){
php dto_gen.php `
 --dir=./schemas `
   --excludeRelDir=/defs/ `
    --targetDir="./app/Dtos" `
     --targetNamespace="App\Dtos" `
     --schemaNamePattern="/^[^\.]*\.json$/" `
      --force
  if($LASTEXITCODE -ne 0){
    echo "`n--------------ERROR: $LASTEXITCODE ENCAUNTERED WHILE GENERATING DTOS--------------`n"
  }
}
