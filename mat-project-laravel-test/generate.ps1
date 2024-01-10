php schema_gen.php `
  --dir=./schemas `
   --sep=/ `
    --outSep=/ `
     --schemaNamePattern="/.special.json/" `
      --force
$CONTINUE = $LASTEXITCODE -eq 0
if (!$CONTINUE) {
  echo "`n--------------ERROR: $LASTEXITCODE ENCAUNTERED WHILE GENERATING SCHEMAS--------------`n"
  $reply = Read-Host -Prompt "Continue?[y/n]"
  $CONTINUE = $reply -eq 'y'
}
if($CONTINUE){
php dto_gen.php `
 --dir=./schemas `
  --sep=/ `
   --excludeRelDir=/defs/ `
    --targetDir="./app/Dtos" `
     --targetNamespace="App\Dtos" `
     --schemaNamePattern="/^[^\.]*\.json$/" `
      --force
  if($LASTEXITCODE -ne 0){
    echo "`n--------------ERROR: $LASTEXITCODE ENCAUNTERED WHILE GENERATING DTOS--------------`n"
  }
}
