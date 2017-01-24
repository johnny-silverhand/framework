<?php
/**
 * spiral
 *
 * @author    Wolfy-J
 */

namespace Spiral\Tests\Translator;

use Spiral\Tests\BaseTest;

class IndexerTest extends BaseTest
{
    public function testIndex()
    {
        $this->assertSame(0, $this->app->console->run('i18n:index')->getCode());
        $this->assertNotEmpty(
            $this->app->translator->getCatalogue()->domainMessages('validation')
        );
    }
}