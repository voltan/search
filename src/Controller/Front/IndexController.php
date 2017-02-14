<?php
/**
 * Pi Engine (http://pialog.org)
 *
 * @link            http://code.pialog.org for the Pi Engine source repository
 * @copyright       Copyright (c) Pi Engine http://pialog.org
 * @license         http://pialog.org/license.txt BSD 3-Clause License
 */

namespace Module\Search\Controller\Front;

use Pi;
use Pi\Mvc\Controller\ActionController;
use Pi\Paginator\Paginator;

/**
 * Search controller
 *
 * @author  Taiwen Jiang <taiwenjiang@tsinghua.org.cn>
 */
class IndexController extends ActionController
{
    /**
     * Search in available modules
     *
     * @return void
     */
    public function indexAction()
    {
        $module = $this->params('m');
        $service = $this->params('s');
        if ($module) {
            $this->searchModule($module);
        } elseif ($service) {
            $this->searchService($service);
        } else {
            $this->searchGlobal();
        }
    }

    /**
     * Search in a specific module
     */
    public function moduleAction()
    {
        $this->searchModule();
    }

    /**
     * Search by external service
     */
    public function serviceAction()
    {
        $this->searchService();
    }

    /**
     * Search by ajax method
     */
    public function ajaxAction()
    {
        $query = $this->params('q');
        $modules = $this->getModules($query);
        $list = array();

        if ($query) {
            $total = 0;
            if (!$this->checkFlood()) {
                $list[] = array(
                    'class' => '',
                    'title' => __('Submission flood detected, please wait for a while.'),
                    'url' => '#',
                    'icon' => '',
                    'image' => '',
                );
            } else {
                $terms = Pi::api('api', 'search')->parseQuery($query);
                if ($terms) {
                    $limit = $this->config('leading_limit');
                    $result = $this->query($terms, $limit);
                    foreach ($result as $name => $data) {
                        $total += $data->getTotal();
                        $totalDate = $data->getTotal();
                        if ($totalDate > 0) {
                            $list[] = array(
                                'class' => ' class="dropdown-header"',
                                'title' => sprintf(__('Search result of %s on %s'), $query, $modules[$name]['title']),
                                'url' => $modules[$name]['url'],
                                'icon' => $modules[$name]['icon'],
                                'image' => '',
                            );
                            foreach ($data as $item) {
                                $image = '';
                                if (isset($item['image']) && !empty($item['image'])) {
                                    $image = $item['image'];
                                }
                                $list[] = array(
                                    'class' => '',
                                    'title' => $item['title'],
                                    'url' => $item['url'],
                                    'icon' => '',
                                    'image' => $image,
                                );
                            }
                            $list[] = array(
                                'class' => ' class="text-right"',
                                'title' => __('See more results'),
                                'url' => Pi::api('api', 'search')->moreLink($query, $name, $modules[$name]['url']),
                                'icon' => '',
                                'image' => '',
                            );
                        }
                    }
                    if (empty($list) && $total == 0) {
                        $list[] = array(
                            'class' => ' class="search-info-text"',
                            'title' => __('No result found.'),
                            'url' => '#',
                            'icon' => '',
                            'image' => '',
                        );
                    }
                } else {
                    $list[] = array(
                        'class' => ' class="search-info-text"',
                        'title' => __('Please input term for search'),
                        'url' => '#',
                        'icon' => '',
                        'image' => '',
                    );
                }
            }
        } else {
            $list[] = array(
                'class' => '',
                'title' => __('Please input term for search'),
                'url' => '#',
                'icon' => '',
                'image' => '',
            );
        }

        return $list;
    }

    /**
     * Perform global search
     *
     * @return void
     */
    protected function searchGlobal()
    {
        $query = $this->params('q');
        $modules = $this->getModules($query);

        if ($query) {
            $total = 0;
            $terms = array();
            $flood = false;
            if (!$this->checkFlood()) {
                $flood = true;
            } else {
                $terms = Pi::api('api', 'search')->parseQuery($query);
            }

            if ($terms) {
                $limit = $this->config('leading_limit');
                $result = $this->query($terms, $limit);
            } else {
                $result = array();
            }
            foreach ($result as $name => $data) {
                $total += $data->getTotal();
            }
            $this->view()->assign(array(
                'query' => $query,
                'result' => $result,
                'total' => $total,
                'flood' => $flood,
            ));
            $this->view()->setTemplate('search-result');
        } else {
            $this->view()->assign(array(
                'query' => ''
            ));
            $this->view()->setTemplate('search-home');
        }

        $this->view()->assign(array(
            'modules' => $modules,
            'service' => $this->getService(),
            'searchModule' => ''
        ));
    }

    /**
     * Perform module search
     *
     * @param string $module
     *
     * @return void
     */
    protected function searchModule($module = '')
    {
        $query = $this->params('q');
        $page = $this->params('page') ?: 1;
        $module = $module ?: $this->params('m');

        $modules = $this->getModules($query);
        if (!isset($modules[$module])) {
            $this->redirect()->toRoute('search', array('q' => $query));
            return;
        }

        if ($query) {
            $result = array();
            $terms = array();
            $total = 0;
            $limit = 0;
            $flood = false;
            if (!$this->checkFlood()) {
                $flood = true;
            } else {
                $terms = Pi::api('api', 'search')->parseQuery($query);
            }
            if ($terms) {
                $limit = $this->config('list_limit');
                $offset = $limit * ($page - 1);
                $result = $this->query($terms, $limit, $offset, $module);
                $total = $result ? $result->getTotal() : 0;
            }
            if ($total && $total > $limit) {
                $paginator = Paginator::factory($total, array(
                    'limit' => $limit,
                    'page' => $page,
                    'url_options' => array(
                        'params' => array(
                            'm' => $module,
                        ),
                        'options' => array(
                            'query' => array(
                                'q' => $query,
                            ),
                        ),
                    ),
                ));
            } else {
                $paginator = null;
            }

            $this->view()->assign(array(
                'query' => $query,
                'result' => $result,
                'total' => $total,
                'flood' => $flood,
                'paginator' => $paginator,
            ));
            $this->view()->setTemplate('search-module-result');
        } else {
            $this->view()->setTemplate('search-home');
        }
        $this->view()->assign(array(
            'modules' => $modules,
            'searchModule' => $module,
            'service' => $this->getService(),
        ));
    }

    /**
     * Perform external search
     *
     * @param string $service
     *
     * @return void
     */
    protected function searchService($service = '')
    {
        $query = $this->params('q');
        $service = $service ?: $this->params('service');
        if (!$service) {
            $this->redirect()->toRoute('search', array('q' => $query));
            return;
        }

        if ('google' == $service && $code = $this->config('google_code')) {
            $host = $this->config('google_host');
            if (!$host) {
                $host = 'www.google.com';
            } else {
                $host = preg_replace('|^(http[s]?:\/\/)|i', '', $host);
                $host = trim($host, '/');
            }
            $this->view()->assign('google', array(
                'code' => $code,
                'host' => $host,
                'q' => $query,
            ));
            $this->view()->setTemplate('search-google');

            return;
        }

        $data = $this->getService($service);
        if (!$data) {
            $this->redirect()->toRoute('search', array('q' => $query));
            return;
        }
        $url = call_user_func($data['url'], $query);
        header('location: ' . $url);

        exit();
    }

    /**
     * Do search query
     *
     * @param array $terms
     * @param int $limit
     * @param int $offset
     * @param string|array $in
     *
     * @return array
     */
    protected function query(array $terms, $limit = 0, $offset = 0, $in = array())
    {
        $moduleSearch = Pi::registry('search')->read();
        $moduleList = Pi::registry('modulelist')->read();
        $modules = (array)$in;
        $result = array();
        $condition = array();
        $columns = array();

        // Save search log
        if ($this->config('save_log')) {
            Pi::api('log', 'search')->saveLog($terms);
        }

        // Set custom fields
        if (!empty($this->config('search_field'))) {
            $fields = explode(',', $this->config('search_field'));
            foreach ($fields as $field) {
                $field = strtolower($field);
                if(preg_match("/^[a-z0-9_]+$/", $field) == 1) {
                    $columns[] = $field;
                }
            }
        }

        if ($this->config('search_in')) {
            $modulesSpecified = explode(',', $this->config('search_in'));
            $modulesSpecified = array_map('trim', $modulesSpecified);
            $list = array();
            foreach ($modulesSpecified as $name) {
                if (isset($moduleSearch[$name])) {
                    $list[$name] = $moduleSearch[$name];
                }
            }
            $moduleSearch = $list;
        }

        foreach ($moduleSearch as $name => $callback) {
            if ($modules && !in_array($name, $modules)) {
                continue;
            }
            if (!isset($moduleList[$name])) {
                continue;
            }
            $searchHandler = new $callback($name);
            $result[$name] = $searchHandler->query($terms, $limit, $offset, $condition, $columns);
        }

        if (is_scalar($in)) {
            $result = isset($result[$in]) ? $result[$in] : array();
        }

        return $result;
    }

    /**
     * Get modules available for search
     *
     * @param string $query
     *
     * @return array
     */
    protected function getModules($query = '')
    {
        $moduleSearch = Pi::registry('search')->read();
        $moduleList = Pi::registry('modulelist')->read();
        $modules = array();

        if ($this->config('search_in')) {
            $modulesSpecified = explode(',', $this->config('search_in'));
            $modulesSpecified = array_map('trim', $modulesSpecified);
            $list = array();
            foreach ($modulesSpecified as $name) {
                if (isset($moduleSearch[$name])) {
                    $list[$name] = $moduleSearch[$name];
                }
            }
            $moduleSearch = $list;
        }

        foreach (array_keys($moduleSearch) as $name) {
            if (!isset($moduleList[$name])) {
                continue;
            }
            $node = $moduleList[$name];
            $url = Pi::url($this->url(
                '',
                array(
                    'action' => 'module',
                    'm' => $name
                ),
                array(
                    'query' => array(
                        'q' => $query,
                    ),
                )
            ));
            $modules[$name] = array(
                'id' => $node['id'],
                'title' => $node['title'],
                'icon' => $node['icon'],
                'url' => $url,
            );
        };

        return $modules;
    }

    /**
     * Get third-party search service list
     *
     * @param string $service
     *
     * @return array
     */
    protected function getService($service = '')
    {
        $home = parse_url(Pi::url('www'), PHP_URL_HOST);
        //$home = 'pialog.org'; // For localhost test

        $googleQuery = function ($query) use ($home) {
            $home = preg_replace('/^(http[s]?:\/\/)/i', '', $home);
            $pattern = 'http://google.com?#newwindow=1&q=site:%s+%s';
            $link = sprintf($pattern, urlencode($home), urlencode($query));

            return $link;
        };
        $bingQuery = function ($query) use ($home) {
            $home = preg_replace('/^(http[s]?:\/\/)/i', '', $home);
            $pattern = 'http://bing.com?q=site:%s+%s';
            $link = sprintf($pattern, urlencode($home), urlencode($query));

            return $link;
        };
        $baiduQuery = function ($query) use ($home) {
            $code = $this->config('baidu_code');
            if ($code) {
                $pattern = 'http://zhannei.baidu.com/cse/search?s=%s&q=%s';
                $link = sprintf($pattern, urlencode($code), urlencode($query));
            } else {
                $pattern = 'http://www.baidu.com/s?wd=site:(%s)+%s';
                $link = sprintf($pattern, urlencode($home), urlencode($query));

            }

            return $link;
        };
        $sogouQuery = function ($query) use ($home) {
            $home = preg_replace('/^(http[s]?:\/\/)/i', '', $home);
            $pattern = 'http://sogou.com/web?query=site:%s+%s';
            $link = sprintf($pattern, urlencode($home), urlencode($query));

            return $link;
        };

        $list = array();

        if($this->config('google_enable')){
            $list['google'] = array(
                'title' => __('Google'),
                'url' => $googleQuery,
            );
        }

        if($this->config('bing_enable')){
            $list['bing'] = array(
                'title' => __('Bing'),
                'url' => $bingQuery,
            );
        }

        if($this->config('baidu_enable')){
            $list['baidu'] = array(
                'title' => __('Baidu'),
                'url' => $baiduQuery,
            );
        }

        if ($service) {
            $result = isset($list[$service]) ? $list[$service] : null;
        } else {
            $result = $list;
        }

        return $result;
    }

    /**
     * Check against submission flood
     *
     * @return bool
     */
    protected function checkFlood()
    {
        $result = true;
        $interval = $this->config('search_interval');
        $uid = Pi::service('user')->getId();
        if (!$uid) {
            $interval = $interval ?: $this->config('search_interval_anonymous');
        }

        if ($interval) {
            $lastSearch = $_SESSION['SEARCH_LAST_QUERY'];
            $_SESSION['SEARCH_LAST_QUERY'] = time();
            if ($lastSearch + $interval > time()) {
                $result = false;
            }
        }

        return $result;
    }
}
