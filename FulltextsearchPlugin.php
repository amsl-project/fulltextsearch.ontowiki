<?php

/**
 * This file is part of the {@link http://ontowiki.net OntoWiki} project.
 *
 * @copyright Copyright (c) 2012, {@link http://aksw.org AKSW}
 * @license   http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */
require_once 'OntoWiki/Plugin.php';
require_once realpath(dirname(__FILE__)) . '/classes/IndexHelper.php';
class FulltextsearchPlugin extends OntoWiki_Plugin
{
    
    public function onIndexAction($event) {
        $resource = $event->resource;
        $model = $event->model;
        $indexHelper = new IndexHelper($this->_privateConfig);
        $indexHelper->triggerReindex($event->resource);
    }
    
    public function onDeleteResourceAction($event) {
        $model = $event->model;
        $indexHelper = new IndexHelper($this->_privateConfig);
        $resources = $event->resources;
        if (!is_array($resources)) {
            $resources = array($resources);
        }
        foreach ($resources as $resource) {
            if ($this->canBeDeleted($resource)) {
                OntoWiki::getInstance()->logger->info('FulltextsearchPlugin: resource ' . $resource . ' can be deleted');
                $indexHelper->triggerDeleteResource($resource);
            } else {
                OntoWiki::getInstance()->logger->info('FulltextsearchPlugin: resource ' . $resource . ' cannot be deleted');
                $indexHelper->triggerReindex($resource);
            }
        }
    }
    
    public function onReindexAction($event) {
        $resource = $event->resource;
        $model = $event->model;
    }
    
    /**
     * Checks whether the resource still exists in another knowledge base.
     * @param  $resource
     * @return type of $resource
     */
    private function canBeDeleted($resource) {
        $_owApp = OntoWiki::getInstance();
        $store      = $_owApp->erfurt->getStore();
        $graph      = $_owApp->selectedModel;
        $model      = new OntoWiki_Model_Resource($store, $graph, (string)$resource);
        $predicates = $model->getPredicates();
        if (count($predicates) == 0) {
            return true;
        } else {
            return false;
        }

    }
}
