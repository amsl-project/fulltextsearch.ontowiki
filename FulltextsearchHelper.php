<?php

/**
 * This file is part of the {@link http://ontowiki.net OntoWiki} project.
 *
 * @copyright Copyright (c) 2013, {@link http://aksw.org AKSW}
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */

/**
 * Helper class for the Fulltextsearch component.
 *
 * @category OntoWiki
 * @package Extensions_Fulltextsearch
 * @copyright Copyright (c) 2012, {@link http://aksw.org AKSW}
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
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
            
        // init view
        if (null === $this->view) {
            $viewRenderer = Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer');
            if (null === $viewRenderer->view) {
                $viewRenderer->initView();
            }
            $this->view = clone $viewRenderer->view;
            $this->view->clearVars();
        }
        
        $this->view->headScript()->appendFile($this->_config->urlBase . 'extensions/fulltextsearch/templates/fulltextsearch/typeahead.bundle.js');
        $this->view->headScript()->appendFile($this->_config->urlBase . 'extensions/fulltextsearch/templates/fulltextsearch/handlebars.js');
        $this->view->headScript()->appendFile($this->_config->urlBase . 'extensions/fulltextsearch/templates/fulltextsearch/search.js');
        $this->view->headLink()->appendStylesheet($this->_config->urlBase . 'extensions/fulltextsearch/templates/fulltextsearch/hint.min.css');
        $this->view->headLink()->appendStylesheet($this->_config->urlBase . 'extensions/fulltextsearch/templates/fulltextsearch/fulltextsearch.css');

    }
}

