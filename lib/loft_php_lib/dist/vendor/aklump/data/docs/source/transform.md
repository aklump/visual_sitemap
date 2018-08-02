---
sort: 50
---
# Transformations

If the transformation is occurring as you pull data out of the subject, then it is probably less verbose to simply use the fourth parameter of the `get()` method, the _value callback_.  If, however, you want to transform the value inside of `$subject`, then this page will advise on how to do that.

## Transform with a function name

The function must only take a single parameter, which is the `$value`.  To use a function with a different signature, wrap it in an anonymous function.

This example will transform a value via `strtoupper()`, observe:
    
    $subject = array('name' => 'bob');
    $obj->getThen($subject, 'name')->call('strtoupper')->set($subject);
    
    // $subject['name'] === 'BOB';

This example will assign it to a different key:

    $subject = array('name' => 'bob');
    $obj->getThen($subject, 'name')->call('strtoupper')->set($subject, 'ucname');
    
    // $subject['name'] === 'bob';
    // $subject['ucname'] === 'BOB';

## Transform with an anonymous function
    
    $from = array('being' => 'dog');
    $obj->getThen($from, 'being')
        ->call(function ($value) {
            $value = str_split($value);
            $value = array_reverse($value);
    
            return implode($value);
        })
        ->set($from);
        
    // $from['being'] === 'god';

## Transform with arguments
Additional arguments to `call()` will be sent to the [callable as arguments](http://php.net/manual/en/language.types.callable.php).  This is the same as `call_user_func_array()` which is used under the hood.

    class MyClass
    {
        public static function Concat()
        {
            return implode('.', func_get_args());
        }
    }
    ...
    $result = $obj->onlyIf($data, 'id')
                  ->call('MyClass::Concat', 'do', 're', 'mi')
                  ->value();
    
    // $result === 'id.do.re.mi';
