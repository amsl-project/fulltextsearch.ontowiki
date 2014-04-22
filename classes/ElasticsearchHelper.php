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

    protected static $client = null;

    public static function getClient()
    {
        if (!isset(static::$client)) {
            static::$client = new static;
            static::init();
        }
        return static::$client;
    }


    /**
     * [init description]
     * @return [type] [description]
     */
    private static function init()
    {
        /**
         * ElasticSearch Configuration
         */
        $params = array();
        $params['hosts'] = array('23.251.151.164');
        $params['connectionParams']['auth'] = array(
            'my_username',
            'my_password',
            'Basic');
        static::$client = new Elasticsearch\Client($params);
       
    }

    /**
     * [search description]
     * @param  [type] $searchTerm [description]
     * @return [type]             [description]
     */
    public function search($searchTerm) {


        $query['index'] = 'bibrm:all';

        // $query['body']['query']['match']['http://purl.org/dc/elements/1.1/title'] = $searchTerm;
        // $json = '{
        //           "query": {
        //             "bool": {
        //               "must": [
        //                 {
        //                   "wildcard": {
        //                     "bibo:periodical.http://purl.org/dc/elements/1.1/title": "*' . strtolower($searchTerm) . '*"
        //                   }
        //                 }
        //               ]
        //             }
        //           }
        //         }';
        $doge = "doge20";

        $logger = OntoWiki::getInstance()->logger;

        if (isset($searchTerm)) {
            $searchTerms = explode(" ", $searchTerm);
            $query['body']['query']['bool']['must']= array();

            foreach ($searchTerms as $term) {
                $wildcard = array(
                    'bibo:periodical.http://purl.org/dc/elements/1.1/title' => strtolower($term) . "*"
                );

                array_push($query['body']['query']['bool']['must'], array(
                    'wildcard' => $wildcard));
            }


            $logger->info($doge . ': ' . print_r($query, true));

            $fullResults = $this->getClient()->search($query);

            $results = array();
            foreach ($fullResults['hits']['hits'] as $hit) {
                $results[] = array(
                    'uri' => $hit['_source']['@id'], 
                    'title' => $hit['_source']['http://purl.org/dc/elements/1.1/title']);
            }
        }

        return $results;
    }
}
