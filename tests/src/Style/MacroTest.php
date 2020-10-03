<?php
/*
 * citeproc-php
 *
 * @link        http://github.com/seboettg/citeproc-php for the source repository
 * @copyright   Copyright (c) 2016 Sebastian Böttger.
 * @license     https://opensource.org/licenses/MIT
 */

namespace Seboettg\CiteProc\Test\Style;

use PHPUnit\Framework\TestCase;
use Seboettg\CiteProc\CiteProc;
use Seboettg\CiteProc\StyleSheet;
use Seboettg\CiteProc\Config\RenderingMode as Mode;

class MacroTest extends TestCase
{

    public function setUp()
    {
        parent::setUp();
    }

    public function testRender()
    {
        $xml = '<style><macro name="title"><choose><if type="book"><text variable="title" font-style="italic"/></if><else><text variable="title"/></else></choose></macro><citation><layout delimiter="; "><text macro="title"/></layout></citation></style>';
        $data = json_decode('[{"title":"Ein herzzerreißendes Werk von umwerfender Genialität","type":"book"},{"title":"Ein nicht so wirklich herzzerreißendes Werk von umwerfender Genialität","type":"thesis"}]');

        $style = new StyleSheet($xml);
        $citeProc = new CiteProc($style);

        $actual = $citeProc->render($data, Mode::CITATION());

        $expected = '<i>Ein herzzerreißendes Werk von umwerfender Genialität</i>; '.
            'Ein nicht so wirklich herzzerreißendes Werk von umwerfender Genialität';

        $this->assertEquals($expected, $actual);
    }
}
