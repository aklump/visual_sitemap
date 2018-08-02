# Loft PHP Library

A collection of PHP Classes by In the Loft Studios.

To ensure all necessary dependencies are loaded you may add this to your _composer.json_ file.

    {
        "autoload": {
            "files": ["lib/loft_php_lib/dist/vendor/autoload.php"],
        }
    }
    
Or you could add this, instead if you will provide the dependencies manually.

    "autoload": {
        "psr-4": {
            "AKlump\\LoftLib\\": "lib/loft_php_lib/dist/src/AKlump/LoftLib"
        }
    }

## Sample `.gitignore` to include everything but the encryption

Quick start, all but the encryption library

    /lib/loft_php_lib/*
    !/lib/loft_php_lib/dist
    !/lib/loft_php_lib/dist/composer*
    !/lib/loft_php_lib/dist/src
    /lib/loft_php_lib/dist/vendor
    /lib/loft_php_lib/dist/src/AKlump/LoftLib/Code/Encryption*

For other configurations, see _ignore.php_.  When you want to use a class, type the following, and the .gitignore snippet will generate for you.

    php ignore.php AKlump/LoftLib/Code/Arrays.php
