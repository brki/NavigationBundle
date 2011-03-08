<?php
namespace Symfony\CMF\Bundle\NavigationBundle\Tests\Functional\Services;

use Symfony\CMF\CoreBundle\Test\CmfTestCase;

use Symfony\CMF\Bundle\NavigationBundle\Services\HierarchyWalker;

/**
 * Test hiearchy walker service
 *
 * @author David Buchmann <david@liip.ch>
 */
class HierarchyWalkerTest extends CmfTestCase
{
    public function setUp()
    {
        $this->assertJackrabbitRunning();
        $this->loadFixture('simpletree.xml');
    }

    public function testGetChildList()
    {
        $walker = new HierarchyWalker($this->getContainer()->get('jackalope.loader'), '/cms/navigation/main');

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
        $walker = new HierarchyWalker($this->getContainer()->get('jackalope.loader'), '/cms/navigation/main');
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

