<?php

function ERROR($LASTEXITCODE,$ACTION) {
  return "\n--------------ERROR: $LASTEXITCODE ENCAUNTERED WHILE $ACTION--------------\n";
}

function GET_CONTINUE() {
  $reply = readline("Continue?[y/n]");
  return $reply === 'y';
}

echo "WTF---------!!!!";

exec("php schema_gen.php --dir=./schemas --sep=/ --schemaNamePattern=\"/\.special\.json/\" --force", $output, $status);
print_r($output);
if ($status !== 0) {
  echo ERROR($status,"GENERATING SCHEMAS");
  $CONTINUE = GET_CONTINUE();
  if (!$CONTINUE) {
    return;
  }
}

exec("php schema_prop_replace.php \"./errors/errors.json\"", $output, $status);
print_r($output);
if ($status !== 0) {
  echo ERROR($status,"REPLACING CODES IN ERRORS");
  $CONTINUE = GET_CONTINUE();
  if (!$CONTINUE) {
    return;
  }
}

exec("php dto_gen.php --dir=./schemas --excludeRelDir=/defs/ --targetDir=\"./app/Dtos\" --targetNamespace=\"App\\Dtos\" --schemaNamePattern=\"/^[^\\.]*\\.json$/\" --force", $output, $status);
print_r($output);
if ($status !== 0) {
  echo ERROR($status,"GENERATING DTOS");
  $CONTINUE = GET_CONTINUE();
  if (!$CONTINUE) {
    return;
  }
}

exec("php ./model_constants_gen.php --modelsDir=\"./app/Models\" --constantsFileNamePattern=\"*.php\" --indentation=4 --prefix=\"COL_\" --indentationChar=\" \" --destinationDir=\"./app/ModelConstants\" --destinationSuffix=\"Constants\"", $output, $status);
print_r($output);
if ($status !== 0) {
  echo ERROR($status,"GENERATING MODEL CONSTANTS");
  $CONTINUE = GET_CONTINUE();
  if (!$CONTINUE) {
    return;
  }
}
