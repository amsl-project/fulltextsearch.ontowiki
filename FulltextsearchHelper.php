<?php

/**
 * This file is part of the {@link http://amsl.technology amsl} project.
 *
 * @author Sebastian Nuck
 * @copyright Copyright (c) 2015, {@link http://ub.uni-leipzig.de Leipzig University Library}
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */

/**
 * Helper class for the Fulltextsearch component.
 *
 * @category OntoWiki
 * @package Extensions_Fulltextsearch
 */
class FulltextsearchHelper extends OntoWiki_Component_Helper
{
    
    /**
     * The module view
     *
     * @var Zend_View_Interface
     */
    public $view = null;
    
    public function init() {
        
        $owApp = OntoWiki::getInstance();
        
        // init view
        if (null === $this->view) {
            $viewRenderer = Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer');
            if (null === $viewRenderer->view) {
                $viewRenderer->initView();
            }
            $this->view = clone $viewRenderer->view;
            $this->view->clearVars();
        }
        
        if ($owApp->erfurt->isActionAllowed('Debug')) {
            $extrasMenu = OntoWiki_Menu_Registry::getInstance()->getMenu('application')->getSubMenu('Extras');
            $extrasMenu->setEntry('Configure Index', $owApp->config->urlBase . 'fulltextsearch/info');
        }
        
        $this->view->headScript()->appendFile($this->_config->urlBase . 'extensions/fulltextsearch/templates/fulltextsearch/js/typeahead.bundle.js');
        $this->view->headScript()->appendFile($this->_config->urlBase . 'extensions/fulltextsearch/templates/fulltextsearch/js/handlebars.js');
        $this->view->headScript()->appendFile($this->_config->urlBase . 'extensions/fulltextsearch/templates/fulltextsearch/js/search.js');
        $this->view->headScript()->appendFile($this->_config->urlBase . 'extensions/fulltextsearch/templates/fulltextsearch/js/info.js');
        $this->view->headScript()->appendFile($this->_config->urlBase . 'extensions/fulltextsearch/templates/fulltextsearch/js/canvasloader.js');
        $this->view->headScript()->appendFile($this->_config->urlBase . 'extensions/fulltextsearch/templates/fulltextsearch/js/masonry.pkgd.min.js');
        $this->view->headScript()->appendFile($this->_config->urlBase . 'extensions/fulltextsearch/templates/fulltextsearch/js/jquery.dataTables.js');


        $this->view->headLink()->appendStylesheet($this->_config->urlBase . 'extensions/fulltextsearch/templates/fulltextsearch/css/hint.min.css');
        $this->view->headLink()->appendStylesheet($this->_config->urlBase . 'extensions/fulltextsearch/templates/fulltextsearch/css/fulltextsearch.css');
        $this->view->headLink()->appendStylesheet($this->_config->urlBase . 'extensions/fulltextsearch/templates/fulltextsearch/css/jquery.dataTables.css');
    }
}

