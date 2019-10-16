# Phine

A very basic PHP parser to minify and art-ify PHP code.

!["This is fine," he said amid the flames.](https://storage.hell.sh/memes/this%20is%20fine.jpg)

## CLI Utils

- `php minify.php <file or folder>` minifies your code
- `php box.php <file> [desired_line_width = 80]` turns your code into a box
- `php parse.php <file>` prints all sections of the parsed code

Rest assured that Phine will not modify your code directly and instead create a copy, e.g. if you run `php box.php test.php` it will create a boxified version of `test.php` at `test.box.php`.

### Cone

If you have [Cone](https://getcone.org), you can install Phine to get the global `phine-minify` and `phine-box` commands:

```Bash
cone add-source https://raw.githubusercontent.com/timmyRS/Phine/master/cone.json
cone update
cone get phine
```
