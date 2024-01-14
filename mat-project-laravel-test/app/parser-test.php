<?php
namespace App{
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/Helpers/CreateTask/ParseEntry.php';
use App\Exceptions\InternalException;
    use App\Helpers\CreateTask\ParseEntry\ParseEntry;
    use App\Utils\DebugUtils;
    use Exception;

$srcCode = <<<'EOF'
<document name="Doplnovacka039-wtf"
orientation="vertical">
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
    <exercise type="FillInBlanks" weight="10">
        <!-- Jméno cvičení by se mohlo používat pro účely referencí, viz. Reference na cvičení
        typ cvičení se používá pro určení syntaxe cvičení váha cvičení neboli důležitost cvičení
        se používá pro vypočtení dosaženého procentuálního skóre celé úlohy, pokud daná úloha
        obsahuje více než 1 cvičení, pokud ne, tak je tento atribut ignorován -->
        <instructions># Doplň na vynechaná místa v **textu** *správnou* možnost.</instructions>
        <content>černý ryb[í/ý/i/y]z, na jaře poletují pyl|y/i|, Dětem se podle šablony dobře
            maloval|i/y| sněhuláci a ozdobné vločky., starověká m|i/y|ncovna, uctiv|í/ý|
            prodavači, nově příchoz|í/ý| ženy, restaurace |Z/z|latý |k/K|říž, vysl|y/i|š ho,
            přečetl |B/b|abičku, |z/s|křížit meče, příval|y/i| vody, |s/z|tíral si pot z čela,
            pozorovala pl|y/i|noucí řeku, návštěvníci odcházel|i/y| zklamaní, chtějí v|y/i|set
            pšenici</content>
    </exercise>
<exercise type="FixErrors" weight="3">
    <!-- Jméno cvičení by se mohlo používat pro účely referencí, viz. Reference na cvičení
    typ cvičení se používá pro určení syntaxe cvičení váha cvičení neboli důležitost cvičení
    se používá pro vypočtení dosaženého procentuálního skóre celé úlohy, pokud daná úloha
    obsahuje více než 1 cvičení, pokud ne, tak je tento atribut ignorován -->
    <instructions># Doplň na vynechaná místa v **textu** *správnou* možnost.</instructions>
    <content>
    <correctText>
    Skákal pes přes oves a přes zelenou louku, šel za ním myslivec s pérem na klobouku.
    </correctText>
    <text>
    Sklaklal ves pres voves a přes selenou lůku, šla za ním myslivec s perem na klobouku.
    </text>
    </content>
</exercise>
    <group>
        <resources>
            <resource>
                TEXT1 Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet Lorem ipsum dolor
                sit amet Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet Lorem ipsum dolor
            </resource>
            <resource>TEXT1 Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet Lorem ipsum
                dolor sit amet Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet Lorem ipsum
                dolor sit amet Lorem ipsum dolor sit amet Lorem ipsum dolor sit amet Lorem ipsum
                dolor sit amet L
            </resource>
        </resources>
        <members>
            <exercise type="FillInBlanks" weight="10">
                <!-- Jméno cvičení by se mohlo používat pro účely referencí, viz. Reference na
                cvičení typ cvičení se používá pro určení syntaxe cvičení váha cvičení neboli
                důležitost cvičení se používá pro vypočtení dosaženého procentuálního skóre celé
                úlohy, pokud daná úloha obsahuje více než 1 cvičení, pokud ne, tak je tento
                atribut ignorován -->
                <instructions># Doplň na vynechaná místa v **textu** *správnou* možnost.</instructions>
                <content>černý ryb[í\/ý/i/y/í/ý]z, na jaře poletují pyl|y/i|, Dětem se podle
                    šablony dobře maloval|i/y| sněhuláci a ozdobné vločky., starověká
                    m|i/y|ncovna, uctiv|í/ý| prodavači, nově příchoz|í/ý| ženy, restaurace
                    |Z/z|latý |k/K|říž, vysl|y/i|š ho, přečetl |B/b|abičku, |z/s|křížit meče,
                    příval|y/i| vody, |s/z|tíral si pot z čela, pozorovala pl|y/i|noucí řeku,
                    návštěvníci odcházel|i/y| zklamaní, chtějí v|y/i|set pšenici</content>
            </exercise>
            <exercise type="FillInBlanks" weight="10">
                <!-- Jméno cvičení by se mohlo používat pro účely referencí, viz. Reference na
                cvičení typ cvičení se používá pro určení syntaxe cvičení váha cvičení neboli
                důležitost cvičení se používá pro vypočtení dosaženého procentuálního skóre celé
                úlohy, pokud daná úloha obsahuje více než 1 cvičení, pokud ne, tak je tento
                atribut ignorován -->
                <instructions># Doplň na vynechaná místa v **textu** *správnou* možnost.</instructions>
                <content>černý ryb[í/ý/i/y]z, na jaře poletují pyl|y/i|, Dětem se podle šablony
                    dobře maloval|i/y| sněhuláci a ozdobné vločky., starověká m|i/y|ncovna,
                    uctiv|í/ý| prodavači, nově příchoz|í/ý| ženy, restaurace |Z/z|latý |k/K|říž,
                    vysl|y/i|š ho, přečetl |B/b|abičku, |z/s|křížit meče, příval|y/i| vody,
                    |s/z|tíral si pot z čela, pozorovala pl|y/i|noucí řeku, návštěvníci
                    odcházel|i/y| zklamaní, chtějí v|y/i|set pšenici</content>
            </exercise>
            <group>
                <resources>
                    <resource>TEXT1 - nested Lorem ipsum dolor sit amet Lorem i</resource>
                    <resource>TEXT2 -nested Lorem idolor sit amet Lorem ipsum dolor sit amet
                        Lorem ipsum dolor sit amet</resource>
                </resources>
                <members>
                    <exercise type="FillInBlanks" weight="10">
                        <!-- Jméno cvičení by se mohlo používat pro účely referencí, viz.
                        Reference na cvičení typ cvičení se používá pro určení syntaxe cvičení
                        váha cvičení neboli důležitost cvičení se používá pro vypočtení
                        dosaženého procentuálního skóre celé úlohy, pokud daná úloha obsahuje
                        více než 1 cvičení, pokud ne, tak je tento atribut ignorován -->
                        <instructions># Doplň na vynechaná místa v **textu** *správnou* možnost.</instructions>
                        <content>černý ryb[íýiy]z, na jaře poletují pyl|y/i|, Dětem se podle
                            šablony dobře maloval|i/y| sněhuláci a ozdobné vločky., starověká
                            m|i/y|ncovna, uctiv|í/ý| prodavači, nově příchoz|í/ý| ženy,
                            restaurace |Z/z|latý |k/K|říž, vysl|y/i|š ho, přečetl |B/b|abičku,
                            |z/s|křížit meče, příval|y/i| vody, |s/z|tíral si pot z čela,
                            pozorovala pl|y/i|noucí řeku, návštěvníci odcházel|i/y| zklamaní,
                            chtějí v|y/i|set pšenici</content>
                    </exercise>
                    <exercise type="FillInBlanks" weight="10">
                        <!-- Jméno cvičení by se mohlo používat pro účely referencí, viz.
                        Reference na cvičení typ cvičení se používá pro určení syntaxe cvičení
                        váha cvičení neboli důležitost cvičení se používá pro vypočtení
                        dosaženého procentuálního skóre celé úlohy, pokud daná úloha obsahuje
                        více než 1 cvičení, pokud ne, tak je tento atribut ignorován -->
                        <instructions># Doplň na vynechaná místa v **textu** *správnou* možnost.</instructions>
                        <content>černý ryb[í/ý/i/y]z, na jaře poletují pyl[y/i], Dětem se podle
                            šablony dobře maloval|i/y| sněhuláci a ozdobné vločky., starověká
                            m|i/y|ncovna, uctiv|í/ý| prodavači, nově příchoz|í/ý| ženy,
                            restaurace |Z/z|latý |k/K|říž, vysl|y/i|š ho, přečetl |B/b|abičku,
                            |z/s|křížit meče, příval|y/i| vody, |s/z|tíral si pot z čela,
                            pozorovala pl|y/i|noucí řeku, návštěvníci odcházel|i/y| zklamaní,
                            chtějí v|y/i|set pšenici</content>
                    </exercise>
                </members>
            </group>
        </members>
    </group>
    <exercise type="FillInBlanks" weight="50">
        <!-- Jméno cvičení by se mohlo používat pro účely referencí, viz. Reference na cvičení
        typ cvičení se používá pro určení syntaxe cvičení váha cvičení neboli důležitost cvičení
        se používá pro vypočtení dosaženého procentuálního skóre celé úlohy, pokud daná úloha
        obsahuje více než 1 cvičení, pokud ne, tak je tento atribut ignorován -->
        <instructions># Doplň na vynechaná místa v **textu** *správnou* možnost.</instructions>
        <content>černý ryb[í/ý/i/y]z, na jaře poletují pyl[y/i], Dětem se podle šablony dobře
            maloval|i/y| sněhuláci a ozdobné vločky., starověká m|i/y|ncovna, uctiv|í/ý|
            prodavači, nově příchoz|í/ý| ženy, restaurace |Z/z|latý |k/K|říž, vysl|y/i|š ho,
            přečetl |B/b|abičku, |z/s|křížit meče, příval|y/i| vody, |s/z|tíral si pot z čela,
            pozorovala pl|y/i|noucí řeku, návštěvníci odcházel|i/y| zklamaní, chtějí v|y/i|set
            pšenici</content>
    </exercise>
</content>
</document>

EOF;
try{
$parseEntry = new ParseEntry();
$res = $parseEntry->parse([$srcCode]);
dump($res);
}
catch (InternalException $e) {
    echo "\nINTERNAL ERROR:\n",
    DebugUtils::jsonEncode([
        'message' => $e->getMessage(),
        'code' => $e->getCode(),
        'context' => $e->context()
    ]),
    "\n";
    var_dump($e->context());
    throw new Exception(previous: $e);
}
}