<?php
namespace Symfony\Cmf\Bundle\NavigationBundle\Service;

use PHPCR\ItemInterface;
use Symfony\Cmf\Bundle\CoreBundle\Helper\PathMapperInterface;

/**
 * visitor to collect entries for a menu hierarchy
 *
 * this visitor collects entries into a liniear list. to get the hierarchy, iterate over each level and
 * have a MenuEntryVisitor visit all children of each node, aggregate the results into nested arrays.
 *
 * @author David Buchmann <david@liip.ch>
 */
class MenuEntryVisitor extends AttributeCollectorVisitor
{
    protected $activeurl;

    /**
     * @param string $titleprop property name of the title to get from the phpcr node
     * @param PathMapperInterface $mapper to map urls to storage ids
     * @param string $activeurl the url to the currently opened menu item to see whether a node is ancestor of that node
     */
    public function __construct($titleprop, PathMapperInterface $mapper, $activeurl)
    {
        parent::__construct($titleprop, $mapper);
        $this->activeurl = $activeurl;
    }

    /**
     * get information from this item.
     *
     * we expect a node, will throw an exception if anything else
     *
     * extract url, title and info whether active into array.
     * active is determined as: the node url is a prefix of the activeurl.
     * TODO: is the definition of active as being part of the url a simplified assumption? should we rather let the mapper decide?
     */
    public function visit(ItemInterface $item)
    {
        if (! $item instanceof NodeInterface) {
            throw new Exception('Internal error: did not expect to visit a non-node object');
        }

        $url = $this->mapper->getUrl($item->getPath());
        $title = $item->getPropertyValue($this->titleprop);
        $active = (strncmp($url, $this->activeurl, strlen($url)) === 0);

        $this->tree[$url] = array('url' => $url, 'title' => $title, 'active' => $active, 'node' => $item);
    }
}
