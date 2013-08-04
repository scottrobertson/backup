Dropbox Backup
======

A backup system written in PHP that uses the Dropbox API.

I recommend that you set this up on an hourly cron, which will overwrite the file every hour (the files are stored in a 'ymd' folder), however Dropbox will keep all 24 revisions for that day for 30 days.

You can currently backup:
 - MySQL
 - MongoDB

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
