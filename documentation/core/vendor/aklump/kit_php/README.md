## Summary
A PHP implementation to parse CodeKit's [Kit language by Bryan Jones](http://incident57.com/codekit/kit.php) .

## Usage
To compile all `.kit` files in a directory, use the following snippet of code as a starting point.  Refer to the class documentation for more info.  The first argument is the source directory to look for `.kit` files and the second argument is the output directory.

    $obj = new Compiler('kit', 'html');
    $obj->apply();
    
## Testing
PHPUnit tests are provided.

    Aaron-Klumps-MacBook-Pro:kit_php aklump$ cd tests
    Aaron-Klumps-MacBook-Pro:tests aklump$ phpunit .
    PHPUnit 3.7.28 by Sebastian Bergmann.
    
    ..........
    
    Time: 24 ms, Memory: 9.50Mb
    
    OK (10 tests, 46 assertions)

##Contact
* **In the Loft Studios**
* Aaron Klump - Developer
* PO Box 29294 Bellingham, WA 98228-1294
* _aim_: theloft101
* _skype_: intheloftstudios
* _d.o_: aklump
* <http://www.InTheLoftStudios.com>