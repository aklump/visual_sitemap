---
noindex: true
---
# Data

## Summary
Provides a common means for getting data from objects or arrays with default option such as Lodash's get method.  Other methods for working with array/object data will be added in time.

## Rationale
Given a multidimensional array, in vanilla PHP you will do this:

    print isset($multi_array['do']['re']) ? $multi_array['do']['re'] : 'default';

Using this class you would do this:

    $data = new Data;
    print $data->get($multi_array, 'do.re', 'default');

Not too impressive... but wait... imagine when you have complex objects, like say a Drupal 7 field value translated into spanish.

    print isset($node->field_description['es']['1']['value']) ? $node->field_description['es']['1']['value'] : '';
    
    // vs...
    
    print $data->get($node, 'field_description.1.value', '');
    
Or when you need to work in Drupal 8 for a few days.

    print isset($node->field_description) ? $node->get('field_description')->get(1)->value : '';
    
    vs.
    
    print $data->get($node, 'field_description.1.value', '');
    
This is where a consistent interface approach starts to make sense.  By the way, there is a Drupal module that uses a different implementation of this class which can be found [here](https://www.drupal.org/project/data_api).

## Default value
Every call to ::get can have a default value, which removes the need for if/thens or issets.
    
    $country = $data->get($record, 'home.address.country', 'U.S.');

## Callback
You can pass a callback to process the value such as loading a record by id.

    $url = $data->get($record, 'picture.id', 'http://mysite.com/default.jpg', function($id) {
        return load_url_by_record_id($id);
    });

## Details
    <?php
    use AKlump\Data\Data;
    
    $data = new Data;
    
    // Let's create a data subject, from which we want to pull data.
    $a = array('b' => array('c' => 'd'));
    
    // First let's pull data that exits.
    // Because $a['b']['c'] has a value 'd', it will be returned.  Default is ignored.
    $value = $data->get($a, 'b.c', 'e');
    $value === 'c';
    
    // Because $a['b']['z'] is not set then the default value comes back, 'e'.
    $value = $data->get($a, 'b.z', 'e');
    $value === 'e';
    
    // This interface works on objects or arrays, regardless.  Let's convert this to a nested object using json functions.
    $a_object = json_decode(json_encode($a));
    
    // Make the same call and you'll get the same answer.
    $value = $data->get($a, 'b.c', 'e');
    $value === 'c';

## Data::set()
Unconditionally sets a value at path.

## Data::ensure()
Ensures that a path is set, does not overwrite if the key/property exists.

## Data::fill()
This is a conditional setter method.  It will fill in only if a path is empty (or based on some other test see example 3).

### Example 1
    <?php
    // In this case the value is filled in.
    $array = array('do' => '');
    $data->fill($array, 'do', 're');
    
    // The value is filled in because the current value is empty.
    $array === array('do' => 're');
    
### Example 2
    <?php
    // In this case the value is NOT.
    $array = array('do' => null);
    $data->fill($array, 'do', 're', 'strict');
    
    // The old value of null remains because 're' is a string and the current value not a string; even though it's empty, it will not be replaced because we've used the 'strict' test.
    $array === array('do' => null); 
      
### Example 3
    <?php
    // In this case the value is replaced based on a callback.
    $array = array('do' => 45);
    $data->fill($array, 'do', 're', function ($current, $exists) {
        return $exists && is_numeric($current);
    });
    
    // The value is replaced because our custom callable tested it as a number and returned true.
    $array === array('do' => 're');

## Acknowledgments
* Thank you Aaron Jensen (https://github.com/aaronjensen) for introducing me to this concept.
* https://lodash.com/docs/4.16.2#get
