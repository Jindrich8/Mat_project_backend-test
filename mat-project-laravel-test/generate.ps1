
function ERROR($ACTION) {
  return "`n--------------ERROR: $LASTEXITCODE ENCAUNTERED WHILE $ACTION--------------`n"
}

function GET-CONTINUE {
  $reply = Read-Host -Prompt "Continue?[y/n]"
  return $reply -eq 'y'
}


echo "WTF---------!!!!";
php schema_gen.php `
  --dir=./schemas `
  --sep=/ `
  --schemaNamePattern="/.special.json/" `
  --force
if ($LASTEXITCODE -ne 0) {
  echo (ERROR "GENERATING SCHEMAS")
  $CONTINUE = GET-CONTINUE
  if (-not $CONTINUE) {
    return
  }
}

php schema_prop_replace.php "./errors/errors.json"
if ($LASTEXITCODE -ne 0) {
  echo (ERROR "REPLACING CODES IN ERRORS")
  $CONTINUE = GET-CONTINUE
  if (-not $CONTINUE) {
    return
  }
}

php dto_gen.php `
  --dir=./schemas `
  --excludeRelDir=/defs/ `
  --targetDir="./app/Dtos" `
  --targetNamespace="App\Dtos" `
  --schemaNamePattern="/^[^\.]*\.json$/" `
  --force

if ($LASTEXITCODE -ne 0) {
  echo (ERROR "GENERATING DTOS")
  $CONTINUE = GET-CONTINUE
  if (-not $CONTINUE) {
    return
  }
}

php .\model_constants_gen.php `
  --modelsDir="./app/Models" `
  --constantsFileNamePattern="*.php" `
  --indentation=4 `
  --prefix="COL_" `
  --indentationChar=" " `
  --destinationDir="./app/ModelConstants" `
  --destinationSuffix="Constants"

if ($LASTEXITCODE -ne 0) {
  echo (ERROR "GENERATING MODEL CONSTANTS")
  $CONTINUE = GET-CONTINUE
  if (-not $CONTINUE) {
    return
  }
}

