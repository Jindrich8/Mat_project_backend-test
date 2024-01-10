<?php
// example code

$srcCode = <<<'EOF'
    <document name="Doplnovacka039-wtf" time-limit="00:30:00" orientation="vertical">
<description>
DESCRIPTION:
&lt;entity&gt;ENTITY CONTENT&lt;/entity&gt;
Lorem ipsum dolor sit amet 
Lorem ipsum dolor sit amet
Lorem ipsum dolor sit amet
Lorem ipsum dolor sit amet
</description>
<content>
    <!-- časový limit se píše ve formátu: hh:mm:ss, tento atribut je nepovinný -->
    <exercise name="Doplnovacka01" type="doplnovacka" weight="10" data-id='jsjdh'>
        <!-- Jméno cvičení by se mohlo používat pro účely referencí, viz. Reference na cvičení typ cvičení se používá pro určení syntaxe cvičení váha cvičení neboli důležitost cvičení se používá pro vypočtení dosaženého procentuálního skóre celé úlohy, pokud daná úloha obsahuje více než 1 cvičení, pokud ne, tak je tento atribut ignorován -->
        <instructions># Doplň na vynechaná místa v **textu** *správnou* možnost.</instructions>
        <content>černý ryb|í/ý/i/y|z, na jaře poletují pyl|y/i|, Dětem se podle šablony dobře maloval|i/y| sněhuláci a ozdobné vločky., starověká m|i/y|ncovna, uctiv|í/ý| prodavači, nově příchoz|í/ý| ženy, restaurace |Z/z|latý |k/K|říž, vysl|y/i|š ho, přečetl |B/b|abičku, |z/s|křížit meče, příval|y/i| vody, |s/z|tíral si pot z čela, pozorovala pl|y/i|noucí řeku, návštěvníci odcházel|i/y| zklamaní, chtějí v|y/i|set pšenici</content>
    </exercise>
    <group>
        <resources>
            <resource>TEXT1 Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet</resource>
            <resource>TEXT Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet</resource>
        </resources>
        <members>
            <exercise name="Doplnovacka01" type="doplnovacka" weight="10" data-id='jsjdh'>
                <!-- Jméno cvičení by se mohlo používat pro účely referencí, viz. Reference na cvičení typ cvičení se používá pro určení syntaxe cvičení váha cvičení neboli důležitost cvičení se používá pro vypočtení dosaženého procentuálního skóre celé úlohy, pokud daná úloha obsahuje více než 1 cvičení, pokud ne, tak je tento atribut ignorován -->
                <instructions># Doplň na vynechaná místa v **textu** *správnou* možnost.</instructions>
                <content>černý ryb|í/ý/i/y|z, na jaře poletují pyl|y/i|, Dětem se podle šablony dobře maloval|i/y| sněhuláci a ozdobné vločky., starověká m|i/y|ncovna, uctiv|í/ý| prodavači, nově příchoz|í/ý| ženy, restaurace |Z/z|latý |k/K|říž, vysl|y/i|š ho, přečetl |B/b|abičku, |z/s|křížit meče, příval|y/i| vody, |s/z|tíral si pot z čela, pozorovala pl|y/i|noucí řeku, návštěvníci odcházel|i/y| zklamaní, chtějí v|y/i|set pšenici</content>
            </exercise>
            <exercise name="Doplnovacka01" type="doplnovacka" weight="10" data-id='jsjdh'>
                <!-- Jméno cvičení by se mohlo používat pro účely referencí, viz. Reference na cvičení typ cvičení se používá pro určení syntaxe cvičení váha cvičení neboli důležitost cvičení se používá pro vypočtení dosaženého procentuálního skóre celé úlohy, pokud daná úloha obsahuje více než 1 cvičení, pokud ne, tak je tento atribut ignorován -->
                <instructions># Doplň na vynechaná místa v **textu** *správnou* možnost.</instructions>
                <content>černý ryb|í/ý/i/y|z, na jaře poletují pyl|y/i|, Dětem se podle šablony dobře maloval|i/y| sněhuláci a ozdobné vločky., starověká m|i/y|ncovna, uctiv|í/ý| prodavači, nově příchoz|í/ý| ženy, restaurace |Z/z|latý |k/K|říž, vysl|y/i|š ho, přečetl |B/b|abičku, |z/s|křížit meče, příval|y/i| vody, |s/z|tíral si pot z čela, pozorovala pl|y/i|noucí řeku, návštěvníci odcházel|i/y| zklamaní, chtějí v|y/i|set pšenici</content>
            </exercis>
            <group>
        <resources>
            <resource>TEXT1 - nested Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet</resource>
            <resource>TEXT2 -nested Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet</resource>
        </resources>
        <members>
            <exercise name="Doplnovacka01" type="doplnovacka" weight="10" data-id='jsjdh'>
                <!-- Jméno cvičení by se mohlo používat pro účely referencí, viz. Reference na cvičení typ cvičení se používá pro určení syntaxe cvičení váha cvičení neboli důležitost cvičení se používá pro vypočtení dosaženého procentuálního skóre celé úlohy, pokud daná úloha obsahuje více než 1 cvičení, pokud ne, tak je tento atribut ignorován -->
                <instructions># Doplň na vynechaná místa v **textu** *správnou* možnost.</instructions>
                <content>černý ryb|í/ý/i/y|z, na jaře poletují pyl|y/i|, Dětem se podle šablony dobře maloval|i/y| sněhuláci a ozdobné vločky., starověká m|i/y|ncovna, uctiv|í/ý| prodavači, nově příchoz|í/ý| ženy, restaurace |Z/z|latý |k/K|říž, vysl|y/i|š ho, přečetl |B/b|abičku, |z/s|křížit meče, příval|y/i| vody, |s/z|tíral si pot z čela, pozorovala pl|y/i|noucí řeku, návštěvníci odcházel|i/y| zklamaní, chtějí v|y/i|set pšenici</content>
            </exercise>
            <exercise name="Doplnovacka01" type="doplnovacka" weight="10" data-id='jsjdh'>
                <!-- Jméno cvičení by se mohlo používat pro účely referencí, viz. Reference na cvičení typ cvičení se používá pro určení syntaxe cvičení váha cvičení neboli důležitost cvičení se používá pro vypočtení dosaženého procentuálního skóre celé úlohy, pokud daná úloha obsahuje více než 1 cvičení, pokud ne, tak je tento atribut ignorován -->
                <instructions># Doplň na vynechaná místa v **textu** *správnou* možnost.</instructions>
                <content>černý ryb|í/ý/i/y|z, na jaře poletují pyl|y/i|, Dětem se podle šablony dobře maloval|i/y| sněhuláci a ozdobné vločky., starověká m|i/y|ncovna, uctiv|í/ý| prodavači, nově příchoz|í/ý| ženy, restaurace |Z/z|latý |k/K|říž, vysl|y/i|š ho, přečetl |B/b|abičku, |z/s|křížit meče, příval|y/i| vody, |s/z|tíral si pot z čela, pozorovala pl|y/i|noucí řeku, návštěvníci odcházel|i/y| zklamaní, chtějí v|y/i|set pšenici</content>
            </exercise>
        </members>
    </group>
        </members>
    </group>
    <exercise name="Doplnovacka01" type="doplnovacka" weight="10">
        <!-- Jméno cvičení by se mohlo používat pro účely referencí, viz. Reference na cvičení typ cvičení se používá pro určení syntaxe cvičení váha cvičení neboli důležitost cvičení se používá pro vypočtení dosaženého procentuálního skóre celé úlohy, pokud daná úloha obsahuje více než 1 cvičení, pokud ne, tak je tento atribut ignorován -->
        <instructions># Doplň na vynechaná místa v **textu** *správnou* možnost.</instructions>
        <content>černý ryb|í/ý/i/y|z, na jaře poletují pyl|y/i|, Dětem se podle šablony dobře maloval|i/y| sněhuláci a ozdobné vločky., starověká m|i/y|ncovna, uctiv|í/ý| prodavači, nově příchoz|í/ý| ženy, restaurace |Z/z|latý |k/K|říž, vysl|y/i|š ho, přečetl |B/b|abičku, |z/s|křížit meče, příval|y/i| vody, |s/z|tíral si pot z čela, pozorovala pl|y/i|noucí řeku, návštěvníci odcházel|i/y| zklamaní, chtějí v|y/i|set pšenici</content>
    </exercise>
</content>
</document>

EOF;

// group - range(1-3), members
// exercise - name, type, weight, data-id
// content

// check 
$result = [];


function getDomNode(XMLReader $parser){
    return $parser->expand(new DOMDocument());
}

function echoXYStr(XMLReader $parser,$srcCode){
    $y = null;
    $domNode = getDomNode($parser);
    if($domNode){
      $y =  $domNode->getLineNo();
    }
    echo  "Y: ",($y ?? "null"),"\n";
    if($y !== null){
   $lineStr = explode("\n",$srcCode)[$y];
   echo "LineStr: ",$lineStr,"\n";
    }
    
}

//Function to use at the start of an element
$start = function($parser,$element_name,$element_attrs) use($srcCode) {
    echo "ELEMENT '$element_name' START\n";
    //echoXYStr($parser,$srcCode);
};

//Function to use at the end of an element
$stop = function($parser,$element_name) use($srcCode) {
    echo "ELEMENT '$element_name' STOP\n";
  //  echoXYStr($parser,$srcCode);
    echo "<br>";
};

//Function to use when finding character data
$char = function($parser,$data) use($srcCode) {
    echo "ELEMENT DATA '$data'\n";
   // echoXYStr($parser,$srcCode);
};
libxml_clear_errors();
libxml_use_internal_errors(true);
$reader = new XMLReader();
if(!$reader->XML($srcCode,"UTF-8")){
echo "\nCould not load xml\n";
return;
}

function getAttributes(XMLReader $reader){
    if($reader->hasAttributes){
        while($reader->moveToNextAttribute()){
            switch($reader->nodeType){
                case XMLReader::ATTRIBUTE:
                    yield $reader->name => $reader->value;
                default:
                    continue 2;
            }
        }
    }
}

while($reader->read()){
    switch($reader->nodeType){
        case XMLReader::ELEMENT:
            echo "START\n";
            echoXYStr($reader,$srcCode);
            $start($reader,$reader->name,[]);
            echo "ATTRIBUTES\n";
            foreach(getAttributes($reader) as $attr => $value){
                echo "$attr => $value\n";
            }
            echo "END OF ATTRIBUTES\n";
            
            break;
            case XMLReader::END_ELEMENT:
                echo "END\n";
                echoXYStr($reader,$srcCode);
            $stop($reader,$reader->name);
            
            break;
            case XMLReader::TEXT:
                echo "TEXT\n";
                echoXYStr($reader,$srcCode);
                $char($reader,$reader->value);
                break;


    }
}
echo "\n\n-----------ERRORS------------\n";
foreach(libxml_get_errors() as $error){
    var_dump($error);
}
?>