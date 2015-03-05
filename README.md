unframed52
===
"They All Suck" - [Rasmus Lerdorf](https://www.youtube.com/watch?v=anr7DQnMMs0#t=1904).

Use `unframed52` to write new web applications or extend legacy PHP applications with JSON web APIs that run fast, fail safe, limit and verify messages, invalidate web resources properly, schedule jobs reliably and poll queues asynchronously.

Requirements
---
- Don't suck
- Be functional
- Fail fast to HTTP errors
- Use JSON messages and I/O lists
- Limit and verify JSON request body 
- Invalidate web resources with PHP callables and templates
- Cast messages to concurrent scripts
- Schedule jobs and poll queues
- Support PHP 5.2

### Don't suck

    "*Frameworks Execute The Same Code Repeatedly Without Need*".

All PHP frameworks suck in a front controller that will require *all* the application's routes classes and functions, probably with *all* their dependencies, eventually configured. 

This design imply needless execution of code in the PHP interpreter to require sources not needed. Also, it invariably leads to framework implementations with to too many interdependent classes, degrading into needlessly complicated solutions and duplicating the web server functions with cache invalidation done wrong. Yes, that's how bad all PHP framework can suck.

By design `unframed52` includes no routing to support REST interfaces, nor any other kind of application prototype.

Write one PHP script for each function the application. 

For instance, here's a `hello_world` function in a script handling a GET request and replying with a JSON response body :

~~~php
<?php
require_once('unframed52/Unframed.php');

function hello_world(JSONMessage $message) {
    return array(
        "hello" => $message->getString('who', "World")."!"
    );
}

if (unframed_is_server_script(__FILE__)) {
    require_once('unframed52/get_json.php');
    unframed_get_json('hello_world');
}
?>
~~~

Call this script `hello_world.php` and let a web server interpret it so that this request :

~~~
GET /hello_world.php HTTP/1.1
Accept: application/json

~~~

May yield this HTTP response :

~~~
HTTP/1.1 200
Content-Type: application/json
Content-Length: 20
Cache-Control: no-cache, no-store: 0

{"hello":"World!"}
~~~

And let the web server control access, dispatch to scripts, log requests and report HTTP errors.

### Be functional

    "*Frameworks require too many interdependent classes*".

Note how the `hello_world.php` script above is exclusively made of functions and depends on a single [JSONMessage](https://github.com/laurentszyster/JSONMessage.php) class. As a small library of functions, `unframed52` simply avoids the issues of object oriented design.

Also note how you may reuse the `hello_world` function defined in that `hello_world.php` example. The functional coding style fostered by this library may spare its applications the intractable design questions and the problematic solutions of object oriented programming.

For instance to create a new script that handle the POST method :

~~~php
<?php
require_once('hello_world.php');

if (unframed_is_server_script(__FILE__)) {
    require('unframed52/post_json.php');
    unframed_post_json('hello_world');
}
?>
~~~

That script will handle this request :

~~~
POST /hello_who.php HTTP/1.0
Content-Type: application/json
Content-Length: 18

{"who":"Jonathan"}
~~~

And reply with :

~~~
HTTP/1.1 200
Content-Type: application/json
Content-Length: 20
Cache-Control: no-cache, no-store: 0

{"hello":"Jonathan!"}
~~~

As far as protocols and methods, that's almost it for `unframed52` : just enough to serve HTTP responses with JSON response body for non-idempotent GET and POST requests sent by a JSON client. 

### Fail Fast

    "*Needlessly complicated solutions*".

Even without an ORM, an MVC dispatcher or a template language, PHP frameworks  suck with needlessly complicated code. Because, whatever the size, frameworks try to cope with different system configurations, handle or silence errors and application faults. All defensive programming yields complicated code.

On the contrary `unframed52` scripts are expected to fails fast to an HTTP error. Because failing fast is the only way to fail *reliably* for any applications and because failing to an HTTP error let the web client and server handle it.

For instance, in a dummy `fail_fast.php` script :

~~~php
<?php
require_once('unframed52/Unframed.php');

function fail_fast(JSONMessage $message) {
    throw new Exception('Failed Fast');
}

if (unframed_is_server_script(__FILE__)) {
    require_once('unframed52/post_json.php');
    unframed_post_json('fail_fast');
}
?>
~~~

Throwing an exception yields an HTTP error 500 with a JSON message reporting the PHP exception as body : 

~~~json
HTTP/1.1 500
Content-Type: application/json
Content-Length: 469
Cache-Control: no-cache, no-store: 0

{
    "exception": {
        "message": "Failed Fast",
        "file": "./test\/scripts\/unframed_fail_fast.php",
        "line": 6,
        "trace": [
            "#0 [internal function]: unframed_fail_fast(Object(JSONMessage))",
            "#1 ./src\/get_json.php(106): call_user_func_array('unframed_fail_f...', Array)",
            "#2 ./test\/scripts\/unframed_fail_fast.php(11): unframed_get_json('unframed_fail_f...')",
            "#3 {main}"
        ]
    }
}
~~~

Note that HTTP errors other than 500 won't yield a PHP trace in the JSON response and that error responses to HEAD requests won't yield a response body at all.

### Use JSON messages

Scripts supported by `unframed52` handle JSON messages.

Wether the message is sent as a GET request's query parameters or a POST request's JSON body, `unframed52` scripts use [`JSONMessage`](https://github.com/laurentszyster/JSONMessage.php) to box input and support name and type validation of  message properties.

Functions that handle JSON messages can reply with a PHP array of two forms: a JSON object or a list of JSON strings.

#### Return JSON Object

For instance the `hello_world` function accepts a `JSONMessage` instance and returns a JSON object in the form of a PHP array :

~~~php
function hello_world(JSONMessage $message) {
    return array(
        "hello" => $message->getString('who', "World")."!"
    );
}
~~~

Note how, if the property `who` is missing from the message handled, a default "World" value will be used.

#### JSON I/O Lists

Decoding and encoding JSON can take a significant toll on performances. To maintain speed with message size whenever possible, `unframed52` also supports functions that return a list of JSON strings ready to be contacenated. 

For instance, this function will decode and encoded *nothing*, not even the input JSON string of the `$message` handled :

~~~php
function replyWithIOList(JSONMessage $message) {
    return array('1', '2', '3', $message->encoded());
}
~~~

If the input `$message` was empty, this JSON string would be sent as a response body : 

~~~json
[1,2,3,{}]
~~~

The response will only be marginaly slower if we had many large and deep JSON strings instead of a few numbers and an empty object.

### Limit and Verify Messages

Scripts should be able to limit the request body's size, verify it before JSON is parsed and limit its depth when possible, eventually using HTTP headers to authorize the request.

For instance to limit messages to a maximum of 512 bytes length, a depth of 1 and verify that the body could be a well formed JSON object and not something wildly different : 

~~~php
<?php
require_once('hello_world.php');

function could_be_json_object ($headers, $body) {
    $bodyCount = count($body); 
    if (!(
        count($body) > 1 
        && $body[0] == '{' 
        && $body[$bodyCount-1] == '}'
    )) {
        throw new Unframed('Not a JSON object', 400);
    }
}

if (unframed_is_server_script(__FILE__)) {
    require_once('unframed52/post_json.php');
    unframed_post_json('hello_world', 512, 1, 'could_be_json_object');
}
?>
~~~

So that if a bad client send some 'foobar' instead of a JSON object in the request body to this new `authorized_world.php` script:

~~~
POST /authorized_world.php HTTP/1.0
Content-Type: application/json
Content-Length: 6

foobar
~~~

The script will yied an error 400 : 

~~~
HTTP/1.1 400
Content-Type: application/json
Content-Length: 29
Cache-Control: no-cache, no-store: 0

{"error":"Not a JSON object"}
~~~

More elaborate verification are possible, including verification of cryptographic signatures in HTTP headers.

### Invalidate Web Resources

    "*Duplicating the web server functionality*".

Don't try to serve idempotent requests for HTML, XML, RSS, nor any other protocol than JSON. Also, don't try to cache the responses. Instead let scripts invalidate resources served and cached by the web.

Use the `unframed_www_invalidate` function to create or replace files and let the web server do most of the REST, ie: handle GET and HEAD requests, encode, compress or cache web resources.

Here's the same "Hello World" example, accepting POST request and invalidating JSON and HTML resources that can thereafter be served directly by the web server.

~~~php
<?php
require_once('unframed52/www_invalidate.php');

function hello_world_json (JSONMessage $message) {
    return json_encode($message->array);
}

function hello_world_invalidate (JSONMessage $message) {
    $who = $message->getString('who', 'World');
    return www_invalidate($message, array(
        '/hello/'.$who.'.json' => 'hello_world_json',
        '/hello/'.$who.'.html' => 'hello_world_html.php'
        ));
}

if (unframed_is_server_script(__FILE__)) {
    require_once('unframed52/post_json.php');
    unframed_post_json('hello_world_invalidate');
}
?>
~~~

Note that Unframed52 allows both function and file names, here a JSON encoder function `hello_world_json` to call and a `hello_word_html.php` template to include :

~~~php
<html>
    <head>
        <title>Hello who ?</title>
    </head
    <body>
        <p>Hello <?php 
        echo $unframed->getString('who', 'World'); 
        ?></p>
    </body>
</html>
~~~

Web resources are assumed to be served directly by the web server and publicly available unless specified otherwise in the web server's configuration.

### Concurrent Scripts

By default a PHP interpreter cannot fork or controll processes. One HTTP request is handled by one process only, for a maximum execution time set in seconds. And 60 seconds is a common limit.

In such execution environment how to start concurrent processes ?

Here is a `hello_world_cast.php` script that does not return anything to its caller and is only accessible locally (ie: from the server) :

~~~php
<?php
require_once('hello_world_invalidate.php');

if (unframed_is_server_script(__FILE__)) {
    require_once('unframed52/cast_json.php');
    unframed_cast_json('hello_world_invalidate');
}
?>
~~~

This script will apply `hello_world_invalidate` only *after* an HTTP 200 response has been sent. And it will continute to execute that function when the connection is closed prematurely by the client.

To `unframed_cast` function does exactly that for its applications: POST a JSON message to a URL and quickly close the HTTP connection (ie: set a low enough timeout).

Casting `hello_world_invalidate` through `hello_world_cast.php` from a control script is both fast and safe, for instance from a `hello_world_control.php` :

~~~php
<?php
require_once('unframed52/cast_json.php');

function hello_world_control (JSONMessage $message) {
    $url = unframed_cast_url('/hello_world_cast.php');
    return array(
        'cast' => unframed_cast($url, $message->map)
    );
}

if (unframed_is_server_script(__FILE__)) {
    unframed_post_json('hello_world_control');
}
?>
~~~

Whatever happens in `hello_world_invalidate` will not block or disrupt the execution of `hello_world_control`.

### Schedule Jobs And Poll Queues

When casting messages is effectively possible it is also possible for a script to loop, to cast a message to itself at its end.

An `unframed_loop` function is provided to start, stop and run a simple loop that cast a heartbeat message to one or more concurrent scripts. For instance, to periodically cast a message to `error_log.php` :

~~~php
<?php
require_once('unframed52/cast_json.php');

if (unframed_is_server_script(__FILE__)) {
    unframed_cast_json('error_log');
}
?>
~~~

~~~php
<?php
require 'unframed52/loop_json.php';

unframed_loop(array(
    "/error_log.php"
));
?>
~~~

Note that by default `unframed_loop` implements a simple web API to control the loop.

~~~
GET hello_loop.php
GET hello_loop.php?command=status
GET hello_loop.php?command=start
GET hello_loop.php?command=stop
~~~

...

### Support PHP 5.2

As Paul M. Jones learned, supporting older versions of PHP is a requirement for many application PHP developers. Setting the bar low enough to support all minor versions of PHP since 5.2 won't hurt.