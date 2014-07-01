<?php

/**
 * This file is part of the {@link http://ontowiki.net OntoWiki} project.
 *
 * @copyright Copyright (c) 2013, {@link http://aksw.org AKSW}
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */

require 'vendor/autoload.php';

class ElasticsearchHelper
{
    
    /**
     * The component private config
     *
     * @var Zend_Config
     */
    private static $_privateConfig;
    
    private static $client = null;
    
    private static $index;
    
    /**
     * [ElasticsearchHelper description]
     */
    public function ElasticsearchHelper($privateConfig) {
        static ::$_privateConfig = $privateConfig;
    }
    
    /**
     * To prevent multiple instances of this class, we implement a singleton-like
     * pattern which makes this class static and not instantiable.
     */
    public static function getClient($config) {
        if (!isset(static ::$client)) {
            static ::init($config);
        }
        return static ::$client;
    }
    
    /**
     * Init function.
     */
    private static function init($config) {
        
        /**
         * ElasticSearch Configuration
         */
        $params = array();
        $params['hosts'] = array($config->fulltextsearch->hosts);
        
        static ::$client = new Elasticsearch\Client($params);
    }
    

    /**
     * Returns all available Indices except for the settings index.
     */
    public function getAvailableIndices() {
        $logger = OntoWiki::getInstance()->logger;
        $query = '_aliases';
        $indices = array_keys($this->getClient(static ::$_privateConfig)->indices()->getAliases());

        // remove settings index.
        $indices = array_diff($indices, array("settings"));
        $logger->info('ulafst:' . print_r(($indices), true));
        return $indices;
    }
    
    /**
     * The search function triggered by the autocomplete function.
     * It only returns specific fields and not the whole result set.
     * @param  String $searchTerm the term the user searched for.
     * @return Array $results the array of results containing only
     * the information to be displayed on the autocomplete feature.
     */
    public function search($searchTerm) {
        
        $logger = OntoWiki::getInstance()->logger;
        
        $index = static ::$_privateConfig->fulltextsearch->index;
        $defaultOperator = static ::$_privateConfig->fulltextsearch->defaultOperator;
        $fields = static ::$_privateConfig->fulltextsearch->fields->toArray();
        $dropdownField = static ::$_privateConfig->fulltextsearch->dropdownField;
        
        $logger->info('fulltextsearch: searching for ' . $searchTerm);
        
        $query['index'] = $index;
        if (isset($searchTerm)) {
            $searchTerms = explode(" ", $searchTerm);
            
            $partialQuery = '';
            foreach ($searchTerms as $term) {
                $partialQuery.= $term . "* ";
            }
            $query['body']['query']['query_string']['query'] = $partialQuery;
            $query['body']['query']['query_string']['fields'] = $fields;
            $query['body']['query']['query_string']['default_operator'] = $defaultOperator;
            
            $highlightFields = array();
            foreach ($fields as $field) {
                $tmp = array($field => array('fragment_size' => 500, 'number_of_fragments' => 1));
                array_push($highlightFields, $tmp);
            }
            $query['body']['highlight'] = array('fields' => $highlightFields);
            
            $logger->info('elasticsearch query:' . print_r(($query), true));
            $fullResults = $this->getClient(static ::$_privateConfig)->search($query);
            
            $results = array();
            
            $logger->info('fullresult:' . print_r(($fullResults), true));
            foreach ($fullResults['hits']['hits'] as $hit) {
                if (isset($hit['highlight'])) {
                    $highlight = $hit['highlight'];
                    $highlightValues[] = array_values($highlight); 
                    $highlightValue = $highlightValues[0];
                    $highlightKey = array_keys($highlight)[0];
                    $results[] = array('uri' => $hit['_source']['@id'], 'title' => $hit['_source'][$dropdownField], 'highlight' => $highlightValue, 'highlightKey' => $highlightKey);
                } else {
                    $results[] = array('uri' => $hit['_source']['@id'], 'title' => $hit['_source'][$dropdownField], 'highlight' => '');
                }
            }
        }
        
        return $results;
    }
    
    /**
     * Returns the full result set to a given search term.
     * @param  String $searchTerm The search term.
     * @param  String $indices The indices to be searched.
     * @return array $fullResults The array containing the complete result set.
     */
    public function searchAndReturnEverything($searchTerm, $indices) {
        
        $logger = OntoWiki::getInstance()->logger;
        
        $defaultOperator = static ::$_privateConfig->fulltextsearch->defaultOperator;
        $fields = static ::$_privateConfig->fulltextsearch->fields->toArray();
        $dropdownField = static ::$_privateConfig->fulltextsearch->dropdownField;
        $size = static ::$_privateConfig->fulltextsearch->size;
        
        // if no index was specified ignore the parameter "index" to search all indices
        if ($indices !== '') {
            $query['index'] = $indices;
        }
        if (isset($searchTerm)) {
            $searchTerms = explode(" ", $searchTerm);
            
            // build wildcard query
            $partialQuery = '';
            foreach ($searchTerms as $term) {
                $partialQuery.= $term . "* ";
            }
            $query['body']['size'] = $size;
            $query['body']['query']['query_string']['query'] = $partialQuery;
            $query['body']['query']['query_string']['fields'] = $fields;
            $query['body']['query']['query_string']['default_operator'] = $defaultOperator;
            
            $highlightFields = array();
            foreach ($fields as $field) {
                $tmp = array($field => array('fragment_size' => 500, 'number_of_fragments' => 1));
                array_push($highlightFields, $tmp);
            }
            $query['body']['highlight'] = array('fields' => $highlightFields);
            
            $resultSet = $this->getClient(static ::$_privateConfig)->search($query);
            
            // if no results via wildcard search have been found,
            // a new fuzzy search is triggered
            if ($resultSet['hits']['total'] == 0) {
                $partialQuery = '';
                foreach ($searchTerms as $term) {
                    $partialQuery.= $term . "~ ";
                }
                $query['body']['query']['query_string']['query'] = $partialQuery;
                $resultSet = $this->getClient(static ::$_privateConfig)->search($query);
            }
            
            $result = array();
            $result['resultSet'] = $resultSet;
            $result['query'] = $query;
            
            return $result;
        }
        return null;
    }
    
    /**
     * clone
     *
     * Since this a singleton, cloning is not allowed.
     */
    protected function __clone() {
    }
}
