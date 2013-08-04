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
 bin/console export:all; # Uses "export" from config.json (See below)
```

## Example config.json
```json
{
    "dropbox": {
        "key": "",
        "secret": ""
    },
    "host": "example.com",
    "mysql": {
        "host": "localhost",
        "password": "password",
        "username": "root"
    },
    "export" : [
        "mongodb",
        "mysql"
    ]
}
```
