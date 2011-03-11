<?php
namespace Symfony\Cmf\Bundle\NavigationBundle\Tests\Functional\Service;

use Symfony\Cmf\Bundle\CoreBundle\Test\CmfTestCase;

use Symfony\Cmf\Bundle\CoreBundle\Helper\DirectPathMapper;
use Symfony\Cmf\Bundle\NavigationBundle\Service\HierarchyWalker;

/**
 * Test hiearchy walker service
 *
 * @author David Buchmann <david@liip.ch>
 */
class HierarchyWalkerTest extends CmfTestCase
{
    public function __construct()
    {
        parent::__construct(__DIR__.'/../../Fixtures/');
    }

    public function setUp()
    {
        $this->assertJackrabbitRunning();
        $this->loadFixture('simpletree.xml');
    }

    public function testGetChildList()
    {
        $walker = new HierarchyWalker($this->getContainer()->get('jackalope.loader'), new DirectPathMapper('/cms/navigation/main'));

        $childlist = $walker->getChildList('test/');
        $this->assertEquals(2, count($childlist));
        list ($key, $val) = each($childlist);
        $this->assertEquals('/test/leveltwo', $key);
        $this->assertEquals('nav leveltwo', $val);
        list ($key, $val) = each($childlist);
        $this->assertEquals('/test/otherleveltwo', $key);
        $this->assertEquals('nav otherleveltwo', $val);
        $this->assertEquals('nav otherleveltwo', $childlist['/test/otherleveltwo']);
    }

    public function testGetParents()
    {
        $walker = new HierarchyWalker($this->getContainer()->get('jackalope.loader'), new DirectPathMapper('/cms/navigation/main'));
        $breadcrumb = $walker->getAncestors('test/leveltwo/levelthree');
        $this->assertEquals(3, count($breadcrumb), 'Not right number of ancestors');
        list($key, $val) = each($breadcrumb);
        $this->assertEquals('/', $key);
        $this->assertEquals('Home', $val);
    }

    public function testGetMenu()
    {
        //TODO
    }
}

