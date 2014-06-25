unframed52
===

A functional library of conveniences for database web applications in PHP 5.2.

It rounds up the usual suspects to handle GET requests with a urlencoded query, handle POST requests with a JSON body, perform common SQL transactions and invalidate web resources using PHP templates. It also includes support for interoperability with [JSBN](https://github.com/laurentszyster/jsbn-min.js).

Note that unframed52 does not provide a routing scheme, models or views.

This is not a framework.

Requirements
---
PHP 5.2 or higher with `ignore_user_abort(true)` effectively enabled.

Synopsis
---
Here's unframed52's "Hello World" example, accepting GET queries :

~~~php
<?php
require 'unframed52/get_json.php';

function hello_world ($message) {
    return array(
        'say' => 'Hello '.$message->getString('who', 'World'); 
        );
}

unframed_get_json('hello_world');
?>
~~~

Note that this script will fail fast to an appropriate HTTP error if the method is not GET and may send pretty-printed JSON depending on the request's accept type.

### POST a JSON request, invalidate JSON and HTML resources

Here's the same "Hello World" example, accepting POST request and invalidating JSON and HTML resources that can thereafter be served directly by the web server.

~~~php
<?php
require 'unframed52/post_json.php';
require 'unframed52/www_invalidate.php';

function hello_world_json ($r) {
    return json_encode($r->array);
}

function hello_world ($message) {
    $who = $message->getString('who', 'World');
    return www_invalidate($message, array(
        '/hello/'.$who.'.json' => 'hello_world_json',
        '/hello/'.$who.'.html' => 'hello_world_html.php'
        ));
}

unframed_post_json('hello_world');
?>
~~~

Note that Unframed52 allows both function and file names, here a JSON encoder and a plain 'hello_word_html.php' template :

~~~php
<html>
    <head>
        <title>Hello who ?</title>
    </head
    <body>
        <p>Hello <?= $unframed_resource->getString('who', 'World') ?></p>
    </body>
</html>
~~~

Web resources are assumed to be served directly by the web server and publicly available unless specified otherwise in the web server's configuration. 

To restrict access to private resources, either: update the configuration and add an access controller; use cryptographic keys and hide resources; or store it in an external database.

### POST a JSON request, validate with the database model and invalidate JSON and HTML resources or fail

Unframed52 also provides conveniences for SQL transactions on PDO. Generic conveniences to INSERT, REPLACE, UPDATE, DELETE and SELECT data from any database. And conveniences to map JSON objects to and from an SQL database created from JSON models.

Again, let's say 'Hello World' and cache it but only if we can update a database model with the JSON request value : 

~~~php
<?php
require 'unframed52/post_json.php';
require 'unframed52/www_invalidate.php';
require 'unframed52/sql_json.php';

function hello_world_json ($message) {
    return json_encode($message->array);
}

function hello_world ($message) {
    # validate the API
    $who = $message->getString('who', 'World');
    # declare the JSON data model
    $pdo = unframed_sqlite_json('hello_world.db', 'hello_', array(
        'who' => array(
            'who' => 'World' // primary
            )
        ));
    # execute the SQL transaction
    unframed_sql_transaction(
        $pdo, 
        'unframed_sql_json_replace', 
        array($pdo, 'hello_', 'who', $message->array)
        );
    # invalidate the REST ,-)
    return www_invalidate($message->array, array(
        '/hello/'.$who.'.json' => 'hello_world_json',
        '/hello/'.$who.'.html' => 'hello_world_html.php'
        ));
}

unframed_post_json('hello_world');
?>
~~~

This example of an improbable database of whos has it all: a JSON web API; a JSON data model declaration; SQL transaction and object mapping; plus web resource invalidation.

### GET a list of JSON objects from a database

Getting a list of JSON values from a table created and updated this way is a lot simpler than parsing and then maping JSON objects from SQL rows each time.

~~~php
<?php
require 'unframed52/get_json.php';
require 'unframed52/sql_select.php';

function hello_worlds ($message) {
    return unframed_sql_select_column(
        unframed_sqlite_open('hello_world.db'), 'hello_who', 'who_json'
        );
}

unframed_get_json('hello_worlds', TRUE);
?>
~~~

Unframed52 applications are expected to replace relations one by one and select many at once, as a list of JSON strings ready to be concatenated.

