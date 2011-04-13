<?php
namespace Symfony\Cmf\Bundle\NavigationBundle\Service;

use PHPCR\SessionInterface;
use PHPCR\NodeInterface;
use PHPCR\ItemVisitorInterface;

use Symfony\Cmf\Bundle\CoreBundle\Helper\PathMapperInterface;

/**
 * this service knows about phpcr and builds navigation information
 *
 * each method exists in getX form, where it returns arrays and in visit form
 * that allows to pass your own visitor.
 *
 * Security: Be careful not to pass paths with ../. If you do, you might expose
 * things you do not want to expose, or the service could be confused and throw
 * an error
 *
 * TODO: make getMenu visitor compatible if possible. see getMenu
 *
 * @author David Buchmann <david@liip.ch>
 */
class HierarchyWalker
{
    /**
     * @var PHPCR\SessionInterface
     */
    protected $session;

    /**
     * @var Symfony\Cmf\Bundle\CoreBundle\Helper\PathMapperInterface
     */
    protected $mapper;

    /**
     * node object that is the root of this navigation tree
     * not to be confused with the repository root
     * @var PHPCR\NodeInterface
     */
    protected $rootnode;

    /**
     * title property name for the node visitor
     */
    protected $titleprop;


    /**
     * @param JackalopeLoader $jackalope the jackalope service to get the session from
     * @param PathMapperInterface $mapper to map urls to storage ids
     * @param string $titleprop name of the title property to be returned along with the hierarchy. optional, defaults to name. Only used with the get methods, the visit methods do not rely on this.
     */
    public function __construct($jackalope, PathMapperInterface $mapper, $titleprop = 'label')
    {
        $this->session = $jackalope->getSession();
        $this->mapper  = $mapper;
        $this->titleprop = $titleprop;
        $basepath = $mapper->getStorageId('/');
        $this->rootnode = $this->session->getNode($basepath);
        if ($this->rootnode == null) {
            throw new Exception("Did not find any node at $basepath");
        }
    }

    /**
     * Factory method to create the visitor to collect the child items.
     */
    protected function createChildListVisitor()
    {
        return new AttributeCollectorVisitor($this->titleprop, $this->mapper);
    }

    /**
     * Get the direct children of a node identified by url
     *
     * @param string $url the url (without eventual prefix from routing config)
     * @return array with url => title for each child of the node at $url
     */
    public function getChildList($url)
    {
        $visitor = $this->createChildListVisitor();
        $this->visitChildren($url, $visitor);
        return $visitor->getArray();
    }

    /**
     * Let this visitor visit all direct children of $url
     *
     * @param string $url the url (without eventual prefix from routing config)
     * @param ItemVisitorInterface $visitor the visitor to look at the nodes
     */
    public function visitChildren($url, ItemVisitorInterface $visitor)
    {
        $node = $this->session->getNode($this->mapper->getStorageId($url));
        foreach($node as $child) {
            $child->accept($visitor);
        }
    }

    /**
     * Factory method to create the visitor to collect the ancestor items.
     */
    protected function createAncestorsVisitor()
    {
        return new AttributeCollectorVisitor($this->titleprop, $this->mapper);
    }

    /**
     * Get all ancestors from root node according to mapper down to the parent of the node identified by url
     *
     * @param string $url the url (without eventual prefix from routing config)
     * @return array with url => title, starting with root node, ending with the parent of url
     */
    public function getAncestors($url)
    {
        $visitor = $this->createAncestorsVisitor();
        $this->visitAncestors($url, $visitor);
        return $visitor->getArray();
    }

    /**
     * Let the visitor visit the ancestors from root node according to mapper down to the parent of the node identified by url
     *
     * @param string $url the url (without eventual prefix from routing config)
     * @param ItemVisitorInterface $visitor the visitor to look at the nodes
     */
    public function visitAncestors($url, ItemVisitorInterface $visitor)
    {
        $node = $this->session->getNode($this->mapper->getStorageId($url));
        $i = $this->rootnode->getDepth();
        while(($ancestor = $node->getAncestor($i++)) != $node) {
            $ancestor->accept($visitor);
        }
    }

    /**
     * Factory method to create the visitor to collect menu items.
     *
     * Extend HierarchyWalker to create a different visitor.
     * TODO: this is a cludge to at least allow to control the behaviour to some extent, until we have a real solution (see below)
     *
     * In addition to implement PHPCR\ItemVisitorInterface, the visitor must have a getArray method
     * that returns information about each visited nodes in the format explained at getMenu
     *
     * @param string $url the url to the active node
     * @param string $fake whether to read a fake property as title (useful to read the non-defined root node of the menu tree)
     */
    protected function createMenuVisitor($url, $fake=false)
    {
        return new MenuCollectorVisitor(($fake ? 'jcr:primaryType' : $this->titleprop), $this->mapper, $url);
    }
    /**
     * Build a menu tree leading to this url.
     *
     * Using the depth parameter, you can load more than the nodes in active url and their siblings,
     * i.e. to preload children of other menu items or to build a sitemap
     *
     * The structure is a nested array of arrays with the navigation root as first array.
     * array("url" => "/",
     *       "title" => "X",
     *       "selected" => true, #whether this entry is in the selected path
     *       "node" => [PHPCRNode Object],
     *       "children" => array("/x" => array([node x with maybe children]),
     *                           "/y" => array([node y with maybe children]),
     *                          )
     * );
     * If skiproot is true (the default) the top structure is an array of children instead.
     *
     *
     * TODO: is there a way to refactor this to allow a custom visitor as well?
     * maybe a factory for the visitor that can also decide wheter an element is active or not?
     * pass a visitorfactory that has getVisitor(parent, active, depth, ...)?
     * then the factory would be responsible of aggregating everything together
     *
     * TODO: is the definition of active as being part of the url a simplified assumption? should we rather let the mapper decide?
     *
     * @param string $url the url (without eventual prefix from routing config)
     * @param bool $skiproot whether to not include the root node in the collection, defaults to skipping it
     * @param int $depth depth to follow non-active node children. defaults to 0 (do not follow). -1 means unlimited
     *
     * @return array structure with entries for each node: title, url, active (parent of $url or $url itselves), node (the phpcr node), children (array, empty array on no children. false if not active node and deeper away from active node than depth.). if you skip the root, the uppermost thing is directly an array of children
     */
    public function getMenu($path, $skiproot = true, $depth=0)
    {
        if (! $skiproot) {
            $visitor = $this->createMenuVisitor($path);
        } else {
            $visitor = $this->createMenuVisitor($path, true);
        }
        $this->rootnode->accept($visitor);
        $tree = $visitor->getArray();
        $tree = reset($tree); //visitor just was at the root node, there is exactly one
        $children= $this->getMenuRecursive($tree, $path, $depth, 0);
        if (! $skiproot) {
            $tree['children'] = $children;
        } else {
            $tree = $children;
        }
        return $tree;
    }

    /**
     * Iterate over the menu tree recursively, starting with the children of each record from the MenuCollectorVisitor
     *
     * @param array $parentrecord as returned by MenuCollectorVisitor
     * @param string $path the node path of the active node
     * @param int $depth the depth to which to follow non-active nodes, -1 for unlimited
     * @param int $curdepth current depth recursion is into non-active nodes
     * @return nested array of all children of this node and their children down the active path and others down to $depth
     */
    protected function getMenuRecursive($parentrecord, $path, $depth, $curdepth)
    {
        $visitor = $this->createMenuVisitor($path);
        foreach($parentrecord['node'] as $child) {
            //iterate over that node's children
            $child->accept($visitor);
        }
        $list = $visitor->getArray();
        foreach($list as $key => $record) {
            if ($record['active']) {
                $list[$key]['children'] = $this->getMenuRecursive($record, $path, $depth, 0);
            } elseif ($curdepth < $depth) {
                $list[$key]['children'] = $this->getMenuRecursive($record, $path, $depth, $curdepth + 1);
            } elseif ($depth === -1) {
                $list[$key]['children'] = $this->getMenuRecursive($record, $path, $depth, -1);
            } else {
                $list[$key]['children'] = false;
            }
        }
        return $list;
    }
}
