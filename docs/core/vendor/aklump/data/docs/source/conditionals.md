---
title: Conditionals
sort: 50
---
# Using the "onlyIf" method for conditionals

Let's assume this setup:

    <?php
    $from = ['id' => '123'];
    $to = [];
    $obj = new Data;
    
## Passing values from one to another

The scenario is this: you want to pass a value from one array to another, only if that value is present in the first array.

    $obj->onlyIf($from, 'id')->set($to);
    // $to['id'] === '123'
    
    $obj->onlyIf($from, 'title')->set($to);
    // $to === []

    
Same as above only we will use a new path in the final array:

    $obj->onlyIf($from, 'id')->set($to, 'account.id');

    // $to['account']['id'] === '123'

## Execution stops with the conditional

When a conditional is used, anything chained after it will not do it's job e.g., `filter` will not be called; `call` will not fire callbacks, etc.  This is illustrated in the following examples.

    $word = array('flying' => 'bird');
    $plural = $obj->onlyIf($word, 'flying')->call(function ($value) {
        return $value . 's';
    })->value();
    // $value === 'birds'
    
    $word = array('flying' => 'bird');
    $plural = $obj->onlyIf($word, 'creeping')->call(function ($value) {
        return $value . 's';
    })->value();
    // $value === null

Here is another real world example of how you could validate an incoming request, throwing an exception if the request is invalid.

    $totalPages = get_total_pages();
    $page = $obj->onlyIfHas($_GET, 'page')
              ->call('intval')
              ->call(function ($page) use ($totalPages) {
                  if ($page < 1 || $page > $totalPages) {
                      throw new \InvalidArgumentException("Page number is invalid.");
                  }
                  
                  return $page;
              })
              ->value();
              
* If the incoming request does not contain `$_GET['page']`, `$page` will be set to `null`.
* If the incoming `$_GET['page']` is a valid page number, it will be converted to an int and then assigned to `$page`.
* If `$_GET['page']` is present but outside the range of pages, the exception is thrown.  `$page` will never be set; this is important depending upon how you catch the exception.
       
## Using a test callback

By passing a third argument--a callback that takes the value as it's parameter and returns true if the value should be used--you can customize how this method works.

    $from = array('name' => 'bob');
    $value = $this->data->onlyIf($from, 'name', function ($value) {
        return substr($value, 0, 1) === 'a';
    })->value();
    
    // $value === null;

## In Summary

|  | onlyIf | onlyIfHas | onlyIfNull |
|----------|----------|----------|----------|
| requires key/property | Y* | Y | - |
| passes if null | -* | Y | Y |
| custom test | Y | - | - |

_* you may alter this with a custom test callback_
