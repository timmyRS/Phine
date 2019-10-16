# Phine

A very basic PHP parser to minify and art-ify PHP code.

!["This is fine," he said amid the flames.](https://storage.hell.sh/memes/this%20is%20fine.jpg)

## CLI Utils

- `php minify.php <file or folder>` minifies your code
- `php box.php <file> [desired_line_width = 80]` turns your code into a box
- `php parse.php <file>` prints all sections of the parsed code

Rest assured that Phine will not modify your code directly and instead create a copy, e.g. if you run `php box.php test.php` it will create a boxified version of `test.php` at `test.box.php`.
