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
    protected static $_privateConfig;
    
    protected static $client = null;
    
    private static $index;
    
    /**
     * [ElasticsearchHelper description]
     */
    public function ElasticsearchHelper($privateConfig) 
    {
        static ::$_privateConfig = $privateConfig;
    }
    
    /**
     * To prevent multiple instances of this class, we implement a singleton-like
     * pattern which makes this class static and not instantiable.
     * @param  [type] $config [description]
     * @return [type]         [description]
     */
    public static function getClient($config) 
    {
        if (!isset(static ::$client)) 
        {
            static ::init($config);
        }
        return static ::$client;
    }
    
    /**
     * [init description]
     * @return [type] [description]
     */
    private static function init($config) 
    {
        
        /**
         * ElasticSearch Configuration
         */
        $params = array();
        $params['hosts'] = array($config->fulltextsearch->hosts);
        
        static ::$client = new Elasticsearch\Client($params);
    }
    
    /**
     * [search description]
     * @param  [type] $searchTerm [description]
     * @return [type]             [description]
     */
    public function search($searchTerm) 
    {
        
        $logger = OntoWiki::getInstance()->logger;
        
        $index = static ::$_privateConfig->fulltextsearch->index;
        $defaultOperator = static ::$_privateConfig->fulltextsearch->defaultOperator;
        $fields = static ::$_privateConfig->fulltextsearch->fields->toArray();
        $dropdownField = static ::$_privateConfig->fulltextsearch->dropdownField;
        
        $query['index'] = $index;
        if (isset($searchTerm)) 
        {
            $searchTerms = explode(" ", $searchTerm);
            
            $partialQuery = '';
            foreach ($searchTerms as $term) 
            {
                $partialQuery.= $term . "* ";
            }
            $query['body']['query']['query_string']['query'] = $partialQuery;
            $query['body']['query']['query_string']['fields'] = $fields;
            $query['body']['query']['query_string']['default_operator'] = $defaultOperator;
            
            $query['body']['highlight'] = array('fields' => array('http://purl.org/dc/elements/1.1/title' => array('fragment_size' => 500, 'number_of_fragments' => 1), 'http://purl.org/dc/elements/1.1/publisher' => array('fragment_size' => 500, 'number_of_fragments' => 1), 'http://rdvocab.info/otherTitleInformation' => array('fragment_size' => 500, 'number_of_fragments' => 1)));
            
            // $grumpf = '';
            // foreach ($fields as $field) {
            //     $grumpf .=  '"' . $field . '": {}, ';
            // }
            
            // $query['body']['highlight']['fields'] = '{' . rtrim($grumpf, ", ") . '}';
            $logger->info('elasticsearch query:' . print_r(($query), true));
            $fullResults = $this->getClient(static ::$_privateConfig)->search($query);
            
            $results = array();
            
            $logger->info('fullresult:' . print_r(($fullResults), true));
            foreach ($fullResults['hits']['hits'] as $hit) 
            {
                if (isset($hit['highlight'])) 
                {
                    $highlightValue = array_values($hit['highlight']) [0];
                    $highlightKey = array_keys($hit['highlight']) [0];
                    $results[] = array('uri' => $hit['_source']['@id'], 'title' => $hit['_source'][$dropdownField], 'highlight' => $highlightValue, 'highlightKey' => $highlightKey);
                } else
                {
                    $results[] = array('uri' => $hit['_source']['@id'], 'title' => $hit['_source'][$dropdownField], 'highlight' => '');
                }
            }
        }
        
        // if results have been found, a last result will be appended which provides a link to a result page displaying more than 7 results.
        // if (count($results) >= 1)
        // {
        // $results[] = array('uri' => 'show-all-results', 'title' => '... search for ', 'highlight' => 'only 7 results are currently displayed', 'show-all-results' => 'true');
        
        // }
        return $results;
    }

    /**
     * [search description]
     * @param  [type] $searchTerm [description]
     * @return [type]             [description]
     */
    public function searchAndReturnEverything($searchTerm) 
    {
        
        $logger = OntoWiki::getInstance()->logger;
        
        $index = static ::$_privateConfig->fulltextsearch->index;
        $defaultOperator = static ::$_privateConfig->fulltextsearch->defaultOperator;
        $fields = static ::$_privateConfig->fulltextsearch->fields->toArray();
        $dropdownField = static ::$_privateConfig->fulltextsearch->dropdownField;
        
        $query['index'] = $index;
        if (isset($searchTerm)) 
        {
            $searchTerms = explode(" ", $searchTerm);
            
            $partialQuery = '';
            foreach ($searchTerms as $term) 
            {
                $partialQuery.= $term . "* ";
            }
            $query['body']['query']['query_string']['query'] = $partialQuery;
            $query['body']['query']['query_string']['fields'] = $fields;
            $query['body']['query']['query_string']['default_operator'] = $defaultOperator;
            
            $query['body']['highlight'] = array('fields' => array('http://purl.org/dc/elements/1.1/title' => array('fragment_size' => 500, 'number_of_fragments' => 1), 'http://purl.org/dc/elements/1.1/publisher' => array('fragment_size' => 500, 'number_of_fragments' => 1), 'http://rdvocab.info/otherTitleInformation' => array('fragment_size' => 500, 'number_of_fragments' => 1)));
            
            
            $logger->info('fullResults elasticsearch query:' . print_r(($query), true));
            $fullResults = $this->getClient(static ::$_privateConfig)->search($query);
            $logger->info('fullResults:' . print_r(($fullResults), true));
            return $fullResults;
            
        }        
        return null;
    }
    
    /**
     * clone
     *
     * Since this a singleton, cloning is not allowed.
     */
    protected function __clone() 
    {
    }
}
