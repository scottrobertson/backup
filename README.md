Dropbox Backup
======

A backup system written in PHP that uses the Dropbox API.

I recommend that you set MySQL and MongoDB up on an hourly cron, which will overwrite the file every hour (the files are stored in a 'ymd' folder), however Dropbox will keep all 24 revisions for that day for 30 days. 

Folders should be setup on a weekly cron and will use 'ym' so you have 1 backup, and 4 versions per month.

**Still in very early development, test and test again before use.**

You can currently backup:
 - MySQL
 - MongoDB
 - Folders/Files

## Usage
```bash
 bin/console dropbox:auth # Setup the Dropbox auth tokens
```

```bash
 bin/console export:all # Uses "export" from config.json (See below)
```

or

```bash
 bin/console export:mysql # Export MySQL
 bin/console export:mongo # Export MongoDB
 bin/console export:folders # Export Folders (set in config.json)
```

## Example config.json
```json
{
    "dropbox": {
        "key": "",
        "secret": ""
    },
    "host": "example.com",
    "mongodb": {
        "host": "localhost",
        "port": 232323,
        "password": "password",
        "username": "admin",
        "database": "test"
    },
    "mysql": {
        "host": "localhost",
        "password": "password",
        "username": "root"
    },
    "export" : [
        "mongodb",
        "mysql",
        "folders"
    ],
    "folders" : [
        "/var/www"
    ],
    "exclude_folders" : [
        "/var/www/site.com"
    ]
}
```
