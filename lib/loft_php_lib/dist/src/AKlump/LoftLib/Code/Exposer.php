<?php
/**
 * @file
 * Defines the Exposer class.
 *
 * @ingroup name
 * @{
 */
namespace AKlump\LoftLib\Code;

/**
 * Represents an Exposer object class.
 * 
 * @brief Exposes protected and private methods and properties of an object.
 *
 * Limitations:
 * - does not seem to work when a protected method has pass-by-reference args.
 *
 * @code
 *   class ShyAboutAge {
 *     
 *     protected $age = 56;
 *   
 *     public function getAge() {
 *       return $this->age - 10;
 *     }
 *     
 *     protected function getRealAge() {
 *       return $this->age;
 *     }
 *   }
 *   
 *   $liar     = new ShyAboutAge;
 *   $honestly = new Exposer($liar);
 *   
 *   print $liar->getAge()
 *   > 46
 *   
 *   print $honestly->age;
 *   > 56
 *   
 *   $honestly->age = 40;
 *   print $liar->getAge();
 *   > 30
 *   
 *   print $honestly->age;
 *   > 40
 *   
 *   print $honestly->getAge();
 *   > 30
 *   
 *   print $honestly->getRealAge();
 *   > 40
 * @endcode
 */
class Exposer {
  
  protected $obj;

  public function __construct($obj) {
    $this->obj = $obj;
  }

  protected function reflect() {
    return new \ReflectionClass(get_class($this->obj));
  }

  public function __call($method, $args) {
    $method = $this->reflect()->getMethod($method);
    $method->setAccessible('public');
    
    return $method->invokeArgs($this->obj, $args);    
  }

  public function __set($name, $value) {
    $property = $this->reflect()->getProperty($name);
    $property->setAccessible('public');
    $property->setValue($this->obj, $value);

    return $this;
  }

  public function __get($name) {
    $property = $this->reflect()->getProperty($name);
    $property->setAccessible('public');

    return $property->getValue($this->obj);    
  }
}
