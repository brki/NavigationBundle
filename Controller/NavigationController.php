<?php

namespace Symfony\Cmf\Bundle\CoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * A controller to render navigational elements into simple html that can be styled further
 *
 * If you have more than one menu, you will need different routes to different instances
 * of a navigation controller. The urls must be only the part after such information
 */
class NavigationController extends Controller
{
    protected $walker;

    /**
     * @param HierarchyWalker $walker the service to know about hierarchies
     */
    public function __construct($walker)
    {
        $this->walker = $walker;
    }

    /**
     * make sure the url is valid
     *
     * TODO: this should be improved
     */
    protected function checkUrl($url)
    {
        if (strstr($url, '/../')) {
            throw new Exception('Invalid url');
        }
    }

    /**
     * Render the list of child nodes of an url (linking titles to the urls)
     *
     * @param string url the url you want the children for
     */
    public function childlistAction($url)
    {
        $this->checkUrl();
        $children = $this->walker->getChildList($url);
        return $this->render('SymfonyCmfNavigationBundle:Navigation:childlist.html.twig',
                             array('children' => $children));
    }

    /**
     * Render a breadcrumb to an url. Each item will link to its url.
     *
     * @param string url the url you want the breadcrumb to
     */
    public function breadcrumbAction($url)
    {
        $this->checkUrl();
        $breadcrumb = $this->walker->getBreadcrumb($url);
        return $this->render('SymfonyCmfNavigationBundle:Navigation:breadcrumb.html.twig',
                             array('breadcrumb' => $breadcrumb));
    }

    /**
     * Render a menu open at a url. Each item will link to its url.
     *
     * @param string url the url to the currently open item
     */
    public function menuAction($url)
    {
        $this->checkUrl();
        $menu = $this->walker->getMenu($url);
        return $this->render('SymfonyCmfNavigationBundle:Navigation:menu.html.twig',
                             array('menu' => $menu));
    }

    public function sitemapAction()
    {
        $map = $this->walker->getMenu('/', -1);
        return $this->render('SymfonyCmfNavigationBundle:Navigation:sitemap.html.twig',
                             array('map' => $map));
    }
}
