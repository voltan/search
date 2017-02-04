<?php
/**
 * Pi Engine (http://pialog.org)
 *
 * @link            http://code.pialog.org for the Pi Engine source repository
 * @copyright       Copyright (c) Pi Engine http://pialog.org
 * @license         http://pialog.org/license.txt New BSD License
 */

/**
 * @author Hossein Azizabadi <azizabadi@faragostaresh.com>
 */
namespace Module\Search\Api;

use Pi;
use Pi\Application\Api\AbstractApi;
use Pi\View\Resolver\ModuleTemplate as ModuleTemplateResolver;

/*
 * Pi::api('api', 'search')->parseQuery($query);
 * Pi::api('api', 'search')->localizeQuery($term);
 * Pi::api('api', 'search')->parseStringQuery($query);
 * Pi::api('api', 'search')->template($module);
 * Pi::api('api', 'search')->moreLink($query, $module, $url);
 */

class Api extends AbstractApi
{
    /**
     * Parse search terms and security from query string
     *
     * @param string $query
     *
     * @return array
     */
    public function parseQuery($query = '')
    {
        $result = array();
        // Get config
        $config = Pi::service("registry")->config->read($this->getModule());
        // Set term length
        $length = $config['min_length'];
        // Check for separate query or not
        if ($config['separate_query']) {
            // Text quoted by `"` or `'` should be matched exactly
            $pattern = '`(?:(?:"(?:\\"|[^"])+")|(?:\'(?:\\\'|[^\'])+\'))`is';

            $terms = array();
            $callback = function ($match) use (&$terms) {
                $terms[] = substr($match[0], 1, -1);
                return ' ';
            };
            $string = preg_replace_callback($pattern, $callback, $query);
            $terms = array_merge($terms, explode(' ', $string));

            array_walk($terms, function ($term) use (&$result, $length) {
                $term = _strip($term);
                if (!$length || strlen($term) >= $length) {
                    $result[] = $this->localizeQuery($term);
                }
            });
            $result = array_filter(array_unique($result));
        } else {
            if (!$length || strlen($query) >= $length) {
                $query = _strip($query);
                $result[] = $this->localizeQuery($query);
            }
        }
        // return result as array
        return $result;
    }

    /**
     * Parse localize search terms
     *
     * @param string $query
     *
     * @return array
     */
    public function localizeQuery($query)
    {
        // Get config
        $config = Pi::service("registry")->config->read($this->getModule());
        // Check
        if ($config['localize_query']) {
            switch (Pi::config('locale')) {
                // Set for persian language
                case 'fa':
                    $query = str_replace(
                        array('ي', 'ك', '٤', '٥', '٦', 'ة'),
                        array('ی', 'ک', '۴', '۵', '۶', 'ه'),
                        $query);
                    break;
            }
        }

        return $query;
    }

    /**
     * Parse search terms and security from query string and return string query for module usage
     *
     * @param string $query
     *
     * @return string
     */
    public function parseStringQuery($query)
    {
        // Clean query
        $query = $this->parseQuery($query);
        $query = implode(' ', $query);
        $query = rtrim($query, ' ');
        return $query;
    }

    /**
     * Set custom template path
     *
     * @param string $module
     *
     * @return string
     */
    public function template($module)
    {
        return sprintf('module/%s:front/search-result', $module);
    }

    /**
     * Set more link url
     *
     * @param string $query
     * @param string $module
     * @param string $url
     *
     * @return string
     */
    public function moreLink($query, $module, $url)
    {
        // Set clean query
        $query = $this->parseStringQuery($query);
        // Get config
        $config = Pi::service("registry")->config->read($this->getModule());
        // Check
        if ($config['module_link'] == 'module') {
            switch ($module) {
                case 'shop':
                    $url = Pi::url(Pi::service('url')->assemble('shop', array(
                        'module' => 'shop',
                        'controller' => 'index',
                        'action' => 'index',
                        'slug' => sprintf('#!/search?title=%s', $query),
                    )));
                    break;

                case 'video':
                    $url = Pi::url(Pi::service('url')->assemble('video', array(
                        'module' => 'video',
                        'controller' => 'index',
                        'action' => 'index',
                        'slug' => sprintf('#!/search?title=%s', $query),
                    )));
                    break;

                /* case 'event':
                    $url = Pi::url(Pi::service('url')->assemble('event', array(
                        'module' => 'event',
                        'controller' => 'index',
                        'action' => 'index',
                        'slug' => sprintf('#!/search?title=%s', $query),
                    )));
                    break; */

                /* case 'guide':
                    $url = Pi::url(Pi::service('url')->assemble('guide', array(
                        'module' => 'guide',
                        'controller' => 'index',
                        'action' => 'index',
                        'slug' => sprintf('#!/search?title=%s', $query),
                    )));
                    break; */
            }
        }

        return $url;
    }
}