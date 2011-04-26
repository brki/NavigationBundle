<?php
namespace Symfony\Cmf\Bundle\NavigationBundle\Service;

use PHPCR\ItemVisitorInterface;
use PHPCR\ItemInterface;
use PHPCR\NodeInterface;

use Symfony\Cmf\Bundle\CoreBundle\Helper\PathMapperInterface;

/**
 * visitor to collect url => title into a flat array
 *
 * @author David Buchmann <david@liip.ch>
 */
class AttributeCollectorVisitor implements ItemVisitorInterface
{
    protected $titleprop;
    protected $mapper;
    protected $tree;

    /**
     * @param string $titleprop property name of the title to get from the phpcr node
     * @param PathMapperInterface $mapper to map urls to storage ids
     */
    public function __construct($titleprop, PathMapperInterface $mapper)
    {
        $this->titleprop = $titleprop;
        $this->mapper = $mapper;
        $this->tree = array();
    }

    /**
     * as defined by interface: do something with this item.
     * we expect a node, will throw an exception if anything else
     */
    public function visit(ItemInterface $item)
    {
        if (! $item instanceof NodeInterface) {
            throw new \Exception("Internal error: did not expect to visit a non-node object: $item");
        }

        $url = $this->mapper->getUrl($item->getPath());
        $this->tree[$url] = $item->getPropertyValue($this->titleprop);
    }

    /**
     * @return the aggregated array
     */
    public function getArray()
    {
        return $this->tree;
    }

    /**
     * reset aggregated information to empty array
     */
    public function reset()
    {
        $this->tree = array();
    }
}