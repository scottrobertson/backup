Dropbox Backup
======

A backup system written in PHP that uses the Dropbox API.

You can currently backup:
 - MySQL
 - MongoDB

## Usage
```bash
 bin/console dropbox:auth; # Setup the Dropbox auth tokens
```


```bash
 bin/console export:mysql; # Export MySQL
 bin/console export:mongo; # Export MongoDB
```
or
```bash
 bin/console export:all;
```
