### Summary
A super simple XML to Json converter written in PHP. Takes an XML file or directory of XML files and converts them to json equivalents.

### Installation
Clone this repository, use composer to install dependencies.
```
> git clone https://github.com/whikloj/Xml2Json

> cd Xml2Json

> composer install
```

###  Usage

* Use the `--file` argument to supply a single file
* Use the `--directory` argument to supply a directory of files (only processes files with an `xml` extension).

Also use the `--output` argument to supply an output directory. Defaults to the current directory.

Additional options
* `--overwrite` : Allow existing JSON files to be overwritten, they are skipped by default.
* `--recurse` : When processing a directory, also process sub-directories. It maintains directory hierarchy by nesting found directories
   and files in the `--output` directory. By default it only processes the supplied directory.

#### Examples

1. Process a single file
```
./xml_to_json.php --file /some/path/myFile.xml --output /some/other/path
```
Creates file `/some/other/path/myFile.json`

---

2. Process a single directory
```
./xml_to_json.php --directory /some/directory --output /some/other/place
```

Assuming `/some/directory` contains `resource1.xml` and `resource2.xml`, creates
```
/some/other/path/resource1.json
/some/other/path/resource2.json
```

---

3. Process a directory and all sub-directories
```
./xml_to_json.php --directory /some/nested/directories --output /some/other/place --recurse
```

Assuming `/some/directory` contains:
```
resource1.xml
resource2.xml
more/resource3.xml
others/resource4.xml
```
Creates:
```
/some/other/place/resource1.json
/some/other/place/resource2.json
/some/other/place/more/resource3.json
/some/other/place/others/resource4.json
```


### License
* [MIT](LICENSE)

### Maintainers
* [Jared Whiklo](https://github.com/whikloj)

