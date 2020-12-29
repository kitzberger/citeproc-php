<?php
/*
 * citeproc-php
 *
 * @link        http://github.com/seboettg/citeproc-php for the source repository
 * @copyright   Copyright (c) 2016 Sebastian Böttger.
 * @license     https://opensource.org/licenses/MIT
 */

namespace Seboettg\CiteProc\Test\Rendering\Choose;

use PHPUnit\Framework\TestCase;
use Seboettg\CiteProc\CiteProc;
use Seboettg\CiteProc\Config\Locale;
use Seboettg\CiteProc\Config\RenderingMode;
use Seboettg\CiteProc\Context;
use Seboettg\CiteProc\Exception\CiteProcException;
use Seboettg\CiteProc\Exception\ClassNotFoundException;
use Seboettg\CiteProc\Exception\InvalidStylesheetException;
use Seboettg\CiteProc\Rendering\Choose\Choose;
use Seboettg\CiteProc\Test\TestSuiteTestCaseTrait;
use SimpleXMLElement;
use function Seboettg\CiteProc\loadLocales;

class ChooseTest extends TestCase
{
    use TestSuiteTestCaseTrait;

    private $chooseXml = [
        '<choose><if type="book"><text variable="title" font-style="italic"/></if><else><text variable="title"/></else></choose>',
        '<choose><if is-numeric="volume"><text variable="title"/><text value="; "/><text variable="volume"/></if><else><text variable="title"/></else></choose>'
    ];


    /**
     * @throws ClassNotFoundException
     * @throws InvalidStylesheetException
     * @throws CiteProcException
     */
    public function testIsNumeric()
    {
        $xml = new SimpleXMLElement($this->chooseXml[1]);
        $context = new Context();
        $context->setMode(RenderingMode::BIBLIOGRAPHY());
        $context->setLocale(\Seboettg\CiteProc\Locale\Locale::factory(Locale::EN_US()));
        CiteProc::setContext($context);
        $choose = Choose::factory($xml, null);
        $ret1 = $choose->render(json_decode('{"title":"Ein herzzerreißendes Werk von umwerfender Genialität","volume":2}'));
        $ret2 = $choose->render(json_decode('{"title":"Ein herzzerreißendes Werk von umwerfender Genialität","volume":"non-numeric value"}'));
        $ret3 = $choose->render(json_decode('{"title":"Ein herzzerreißendes Werk von umwerfender Genialität"}'));

        $this->assertEquals("Ein herzzerreißendes Werk von umwerfender Genialität; 2", $ret1);
        $this->assertEquals("Ein herzzerreißendes Werk von umwerfender Genialität", $ret2);
        $this->assertEquals("Ein herzzerreißendes Werk von umwerfender Genialität", $ret3);
    }

    public function testBugfix_github_44()
    {
        $this->runTestSuite("bugfix-github-44");
        $this->runTestSuite("bugfix-choose-github-44");
    }

    public function testConditionEmptyIsUncertainDateFalse()
    {
        $this->runTestSuite("condition_EmptyIsUncertainDateFalse");
    }

    public function testConditionIsNumericMatchNone()
    {
        $this->runTestSuite("condition_IsNumericMatchNone");
    }

    public function testConditionLocatorIsFalse()
    {
        $this->runTestSuite("condition_LocatorIsFalse");
    }

    public function testConditionVariableMatchAll()
    {
        $this->runTestSuite("condition_VariableMatchAll");
    }
}
