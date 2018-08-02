---
sort: 0
---
# Reading Data

Here are some examples of how this class is used to get data out of an object or array.  The examples use an array, but they would work equally well with standard class objects, and other objects with public properties or magic getters, or a `get()` method.

For all examples, assume the following setup:

    <?php
    $data = ['id' => '123', 'email' => 'me@there.com'];
    $obj = new Data;

## Example One: Simple

Simple. No transformation. Data is present.

    $result = $obj->get($data, 'id');
    
    // $result === '123';

## Example Two: Using Default

Simple. With Default. Data not present.

    $result = $obj->get($data, 'name', 'anonymous');
    
    // $result === 'anonymous';

## Example Three: Transformation Callbacks

The callback receives three arguments:

1. The computed value; this will be the default if the path doesn't exist.
1. The original default value.
1. `true` if the path existed; a.k.a. `true` if the default is NOT being used.

Transformation to integer.  Notice that the transformation callback can do anything to the value you want.  It just happens to convert to integer in this example.

    $result = $obj->get($data, 'id', null, function ($value, $default, $exists) {
        return $value ? intval($value) : $default;
    });
    
    // $result === 123

## Example Four: Shorthand method(s)

... instead of callback, try using `getInt()` method, which is far less verbose ...

    $result = $obj->getInt($data, 'id');
    
    // $result === 123

## Example Five: Chaining
A contrived example as you would just use `get()` in such a case, but this demonstrates a point: how to get a value through chaining.  The `getThen()` method returns an instance of the object, not the value.  You have to use the `value()` method to return the value itself.

    $result = $obj->getThen($data, 'id')
                  ->value();
    
    // $result === '123'

## Example Six: Chaining with filter
`getThen()` is really meant for leveraging the `filter()` method.

    $result = $obj->getThen($data, 'email')
                  ->filter(FILTER_SANITIZE_EMAIL)
                  ->value();
    
    // $result === 'me@there.com'
