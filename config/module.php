<?php
/**
 * Pi Engine (http://pialog.org)
 *
 * @link            http://code.pialog.org for the Pi Engine source repository
 * @copyright       Copyright (c) Pi Engine http://pialog.org
 * @license         http://pialog.org/license.txt BSD 3-Clause License
 */

return array(
    'meta'  => array(
        'title'         => _a('Search'),
        'description'   => _a('Search service applications.'),
        'version'       => '1.2.1',
        'license'       => 'New BSD',
        'icon'          => 'fa-search',
    ),
    'author'    => array(
        'Name'      => 'Taiwen Jiang',
        'Email'     => 'taiwenjiang@tsinghua.org.cn',
        'Dev'       => '@lavenderli',
        'UI/UE'     => '@zhangsimon, @loidco',
    ),
    'resource' => array(
        'database'      => array(
            'sqlfile'   => 'sql/mysql.sql',
        ),
        'config'        => 'config.php',
        'permission'    => 'permission.php',
        'page'          => 'page.php',
        'navigation'    => 'navigation.php',
        'block'         => 'block.php',
        'route'         => 'route.php',
    ),
);
