# Changelog

## 0.6.33

### FilePath

* A change was made that will always remove the trailing / from the first element of the return array of `ensureDir`.  This also affects `getDirname`.
* A change was made that will always remove the whitespace from the second element of the return array of `ensureDir`.  This also affects `getBasename`, `getFilename`, and `getExtension`.
* The to() method now returns a new FilePath object of type FilePath::TYPE_FILE and no longer affects the basename on the original object.  This may break your code.

### PdfConverterInterface

* `testConvert()` now throws an exception on fail.


## 0.5.8
* Deprecated class `String`, replaced with `Strings`; please change all instances.

## 0.5.5
* It is now possible to provide a custom extension, such as `.info` instead of `.ini`.

## 0.5
* ConfigFileBasedStorage will now automatically add the extension to the basename if it is not present.  This can be disabled with the option 'auto_extension' = false
* The ConfigYaml extension is now .yaml not .yml per <http://www.yaml.org/faq.html>; if this breaks your app, then extend the class with a new constant.



