---
title: Getting Variables
sort: 5
---
# How to read in from the defined variables

You can use this class to read from variables quite easily.  For example of you want to get the value of `$foo` and you don't know if `$foo` is defined or not, you can do the following and leverage the footprint of `Data`.

    $foo = 'white';
    ...
    $result = $this->data->get(get_defined_vars(), 'foo', 'black');
    
    // $result === 'white'
    
    
    $result = $this->data->get(get_defined_vars(), 'bar', 'black');
    
    // $result === 'black'

`get_defined_vars()` only works in the current scope.  Learn about [get_defined_vars()](http://php.net/manual/en/function.get-defined-vars.php).
