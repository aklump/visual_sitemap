---
sort: 10
---
# Ways to Write Data

## `set()` vs. `ensure()` vs. `fill()`
To understand these methods, lets' use a metaphore.  Imagine a table with nothing on it.  On the floor is a bucket, inside of which is a banana.  Next to the bucket is an apple.

Using `set()` is akin to placing the bucket on the table, removing the banana and putting the apple inside the bucket.

* The table is your _containing variable_.
* The bucket is the _path_ inside the variable.
* The banana is the _old value_ at the path.
* The apple is the _new value_ a the path.

### `ensure()`
Start with an empty table, an empty bucket on the floor and the apple.  After calling ensure, we have a bucket with the apple in it on the table.

Now consider the same scenario but the bucket has the banana in it to start with, and the bucket is on the floor.  After calling ensure, we have a bucket _still with a banana in it_, but the bucket is _on the table_.

Lastly, think of the table with a bucket on it containing the banana.  After calling ensure you would see no change.

When you call `ensure()`, you ensure there is a bucket on the table, if not you put one there.  Then we look inside the bucket on the table to see if it's empty, if so we place the apple inside of it.

### `fill()`
If you understand `ensure()` then you should be able to get `fill()` rather quickly.

Calling fill will not place the bucket on the table, only fill it if it's already on the table AND it's empty.  How a bucket is considered empty is configurable, so refer to the docblocks for that.

### `set()`
So with those out of the way, `set()` is a piece of cake.

Set doesn't care if there is a bucket on the table or not.  Set will walk up to the table grab the bucket, dump it out, set it on the table, and put the apple in it.  No consideration of anything.

## In Summary
|  | set | ensure | fill |
|----------|----------|----------|----------|
| guarantees key/property | Y | Y | - |
| overwrites current value | Y | - | only if empty |

* Use `fill()` if you want to make sure no value is empty, but do not want to add the key/property if non-existent.
* Use `ensure()` if you only care that a key/property is set, but not the value.
* Use `set()` if you want to overwrite current values, while knowing that a key/property is surely set.
* Use the `getThen()->...->set()` chain with a value callback or filter if you just want to transform a value.
