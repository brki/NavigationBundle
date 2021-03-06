<?php

namespace Symfony\Cmf\Bundle\NavigationBundle\Document;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * An entry in the navigation tree
 *
 * This class is just a template, you can use something else with the
 * navigation bundle, as long as you provide the necessary fields.
 *
 * You can modle the following scenarios:
 * <ul>
 * <li>Menu entry that redirects to another navigation item. Will render with
 *     this label but the url for the target navigation entry. On requesting
 *     this entry, a redirect is set to the client. Redirection is path based.
 *     Content and controller are never considered.
 * </li>
 * <li>Normal menu entry with or without referenced content => the controller
 *     is used and if there is referenced content, it is passed to the
 *     controller.
 *     To use the same content in different navigation entries, just link the
 *     same content node from different navigation entries.
 * </li>
 * <li>Hidden entry that is delivered on request but not rendered into the
 *     menu. Children of a hidden entry are not rendered.
 * </li>
 * </ul>
 *
 * @PHPCRODM\Document(repositoryClass="Doctrine\ODM\PHPCR\DocumentRepository", alias="navigation")
 */
class Navigation
{
    /**
     * to create the document at the specified location. read only for existing documents.
     *
     * @PHPCRODM\Id
     */
    protected $path;

    /**
     * @PHPCRODM\Node
     */
    protected $node;

    /**
     * navigation entry label
     *
     * @Assert\NotBlank
     * @PHPCRODM\String()
     */
    protected $label;

    /**
     * navigation entry information
     * i.e. tooltip, sitemap, navigation link title attribute
     *
     * @PHPCRODM\String()
     */
    protected $info;

    /**
     * Invisible navigation items are not rendered in menu tree, but still
     * apear in the breadcrumb.
     *
     * TODO: make this boolean once boolean is fixed
     * @PHPCRODM\Long()
     */
    protected $visible = true;

    /**
     * Reference to another navigation entry this navigation point redirects to
     *
     * This is path based
     *
     * @PHPCRODM\String()
     */
    protected $redirect_to_navigation;

    /**
     * Controller alias for rendering the target content
     *
     * This is always specified to allow for navigation entries with no
     * referenced content.
     * TODO: make it optional even for navigation entries with content and use
     *       default controller and document annotations
     *
     * @PHPCRODM\String()
     */
    protected $controller;

    /**
     * Referenced document
     * TODO: make when reference annotation works
     * @PHPCRODM\Reference
    protected $reference;
    */

    //TODO: $redirect_reference when reference annotation works

    /**
     * Set repository path of this navigation item for creation
     */
    public function setPath($path)
    {
      $this->path = $path;
    }
    public function getPath()
    {
      return $this->path;
    }

    public function setLabel($label)
    {
        $this->label = $label;
    }
    public function getLabel()
    {
        return $this->label;
    }

    public function setInfo($info)
    {
        $this->info = info;
    }
    public function getInfo()
    {
        return $this->info;
    }

    public function setVisible($visible)
    {
        $this->visible = $visible;
    }
    public function getVisible()
    {
        return $this->visible;
    }

    public function setRedirectPath($path)
    {
        $this->redirect_to_navigation = $path;
    }
    public function getRedirectPath()
    {
        return $this->redirect_to_navigation;
    }
    /**
     * Get the node this navigation item redirects to
     * TODO: make this a document instance
     *
    public function getRedirect()
    {
        return $this->node->getProperty('redirect_to_navigation')->getNode();
    }
    */

    public function setController($controller)
    {
        $this->controller = $controller;
    }
    public function getController()
    {
        return $this->controller;
    }

    // TODO: clean this up once mapping documents is implemented

    /**
     * set content for this controller
     *
     * @param NodeInterface $node a node that is saved and has the mix:referenceable mixin type
     */
    public function setReference(\PHPCR\NodeInterface $node)
    {
         $this->node->setProperty('reference', $node->getIdentifier(), \PHPCR\PropertyType::REFERENCE);
    }
    public function getReference()
    {
        if (! $this->node->hasProperty('reference')) {
            return null;
        }
        return $this->node->getProperty('reference')->getNode();
    }

    // ----- End TODO

}

