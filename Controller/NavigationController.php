<?php

namespace Symfony\Cmf\Bundle\NavigationBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\ControllerResolver;
use Symfony\Bundle\FrameworkBundle\Controller\ControllerNameParser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Yaml\Parser;

use Doctrine\ODM\PHPCR\DocumentManager;

use Symfony\Cmf\Bundle\NavigationBundle\Service\HierarchyWalker;
use Symfony\Cmf\Bundle\CoreBundle\Helper\PathMapperInterface;

/**
 * controller for navigation items
 *
 * a navigation item is a document of type Navigation and thus has fields for
 * the controller and referenced content
 */
class NavigationController extends Controller
{
    protected $container;

    protected $dm;

    /**
     * navigation document type because repo can not auto-detect
     */
    protected $documenttype;

    protected $controllers_mapping = array();

    /**
     * name for the navigation route to build links to other languages
     */
    protected $route_name;

    protected $walker;

    protected $mapper;

    public function __construct(ContainerInterface $container,
                                DocumentManager $document_manager,
                                HierarchyWalker $walker,
                                PathMapperInterface $mapper,
                                $route_name)
    {
        $this->container = $container;
        $this->dm = $document_manager;
        $this->route_name = $route_name;
        $this->walker = $walker;
        $this->mapper = $mapper;

        $this->documenttype = $this->container->getParameter('symfony.cmf.navigation.document');
        $this->loadNavigationControllersMapping();
    }

    /**
     * Read the controllers mapping file and create a mapping table
     *
     * TODO: move this to dependency injection and allow to use a different
     * file or add your mappings directly in config.yml
     */
    protected function loadNavigationControllersMapping()
    {
        // Read the yaml mapping file
        $yamlfile = __DIR__.'/../Resources/config/navigation_controllers.yml';
        $yaml = new Parser();
        $data = $yaml->parse(file_get_contents($yamlfile));

        if (!array_key_exists('controllers', $data)) {
            throw new \Exception("Invalid controllers mapping file '$yamlfile'");
        }

        // Search for the mapped controller/action
        $parser = new ControllerNameParser($this->container->get('kernel'));
        $resolver = new ControllerResolver($this->container, $parser);

        //echo "<br/>Reading controllers mapping file '$yamlfile'<br/>";
        foreach($data['controllers'] as $key => $value) {
            try {
                list($controller, $action) = $resolver->getController(new Request(array(), array(), array('_controller' => $value)));
                //echo "&nbsp;&nbsp;$key => ".get_class($controller).".$action<br/>";
                $this->controllers_mapping[$key] = array($controller, $action);
            } catch (\Exception $ex) {
                throw new \Exception("Invalid mapped controller '$value' for the key '$key'");
            }
        }
    }

    /**
     * Gets the navigation entry and calls the controller referenced in the entry.
     *
     * That controller must expect the referenced document, the path to that document and the list of languages
     */
    public function indexAction($url = '')
    {
        //get list of language urls
        $langUrls = array();
        /*
         * TODO: multilang
        foreach($this->lang_chooser->getDefaultLanguages() as $lang) {
            $langUrls[$lang] = $this->generateUrl($this->route_name, array('_locale' => $lang, 'path' => $url));
            //FIXME: have full language name as well
        }
        */

        //there seems to be a bug in symfony: routing eats away the first leading /
        if (strlen($url) == 0 || $url[0] !== '/') {
            $url = "/$url";
        }

        $crpath = $this->mapper->getStorageId($url);
        $repo = $this->dm->getRepository($this->documenttype);
        $page = $repo->find($crpath);

        if ($page == null) {
            throw new NotFoundHttpException("There is no page at $url (internal path '$crpath')");
        }

        //is this a redirect entry?
        $redirect_path = $page->getRedirectPath();
        if (! empty($redirect_path)) {
            $redirect_url = $this->mapper->getUrl($redirect_path);
            if ($redirect_url == $url) {
                throw new \Exception("$url is redirecting to itself");
            }
            return $this->redirect($this->generateUrl('navigation', array('path' => $redirect_url)));
        }

        // Get the referenced node if a referenced path was provided
        $content = null;
        $referenced_node = $page->getReference();
        if (! is_null($referenced_node)) {
            // Get the Document type from the jackalope node. This will fetch data from backend.
            // Jackalope does cache it, so the performance impact is almost 0
            if ($referenced_node->hasProperty('phpcr:alias')) {
                $metadata = $this->dm->getMetadataFactory()->getMetadataForAlias($referenced_node->getPropertyValue('phpcr:alias'));
                $type = $metadata->name;
            } else {
                throw new \Exception("$crpath seems to be no phpcr-odm node, it is missing the alias property.");
                // TODO: have some default document type to load this? best would be if doctrine would handle all this for us
            }

            $content = $this->dm->find($type, $referenced_node->getPath());
        }

        if (array_key_exists($page->getController(), $this->controllers_mapping)) {
            list($controller, $action) = $this->controllers_mapping[$page->getController()];
        } else {
            throw new \Exception("Could not find a controller mapping for '{$page->getController()}'");
        }

        // Execute the referenced action on the controller
        return call_user_func(array($controller, $action), $content, $url, $langUrls);
    }
}
