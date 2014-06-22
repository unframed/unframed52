<?php

require_once(dirname(__FILE__).'/Unframed.php');

unframed_no_script(__FILE__);

/*

Resources are invalidated and templates applied in a convenient order,
from the top to the bottom of the ressource tree, resources first and 
paths second, allways in lexycographical order.

For instance :

    /about.html
    /index.html
    /index.json
    /article/0.html
    /article/0.json

Templates developpers may leverage that sort order to re-use the
side-effects of the template applied for the /index.html page in 
the template that generates the /article/0.html page.

*/

function unframed_www_path_add ($root, $path) {
    $names = explode('/', $path);
    $last = count($names) - 1;
    if ($last > 0) {
        $node = $root;
        for ($index=0; $index < $last; $index++) {
            $name = $names[$index];
            $next = $node[$name];
            if (!isset($next)) {
                $next = array();
                $node[$name] = $next;
            }
            $node = $next;
        }
        $node[$names[$last]] = $path;
    } else {
        $root[$path] = $path;
    }
}

function unframed_www_path_step ($paths, $node) {
    $down = array();
    foreach(sort(array_keys($paths)) as $name) {
        $path = $node[$name];
        if (is_string($path)) {
            array_push($paths, $path);
        } else {
            array_push($down, $name);
        }
    }
    foreach($down as $name) {
        unframed_www_path_step($paths, $node[$name]);
    }
}

function unframed_www_sort ($paths) {
    $root = array();
    foreach ($paths as $path) {
        unframed_www_path_add($root, $path);
    }
    $sorted = array();
    unframed_www_path_step($sorted, $root);
    return $sorted; 
}

/**
 * Include then capture the output of PHP templates, write it in 
 * the static folder `www` under their URL keys. Disable execution time
 * limit on the script by default, eventually set a new one.
 *
 * Note that templates are conveniently sorted by resource paths and applied
 * in that order, so that template side-effects can be leveraged.
 *
 * Also, note that a PDO object can be injected in the template's scope as
 * `unframed_pdo`, a conveniece to add more statements to an opened 
 * transaction or simply select data to fill the template. 
 *
 * @param array $unframed_resource the data used to invalidate the web cache.
 * @param array $unframed_route map URIs to either callable or PHP templates.
 *
 * @return array of catched exceptions keyed by template path.
 */
function unframed_www_invalidate(
    $unframed_resource, 
    $unframed_routes, 
    $unframed_www='./',
    $unframed_php='./'
    ) {
    $unframed_paths = unframed_www_sort(array_keys($unframed_routes));
    $unframed_errors = array();
    foreach ($unframed_paths as $unframed_path) {
        $route = $unframed_routes[$unframed_path];
        if (is_callable($route, TRUE)) {
            try {
                file_put_contents(
                    $unframed_www.$unframed_path, 
                    call_user_func_array($route, $array($unframed_resource))
                    );
            } catch (Exception $e) {
                $unframed_errors[$unframed_path] = $e;
            }
        } else {
            ob_start();
            try {
                include $unframed_php.$route;
                file_put_contents($unframed_www.$unframed_path, ob_get_contents());
            } catch (Exception $e) {
                $unframed_errors[$unframed_path] = $e;
            }
            ob_end_clean();
        }
    }
    return $unframed_errors;
}
