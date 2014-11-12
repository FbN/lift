#lift

## What and Why

Lift is a PHP script for quick sync with FTP server.

The world is full of wonderfull tecnology that helps you deploy your project in marvelous ways: ssh tools, cloud API, docker, git deploy, bla bla bla. 

Unfortunately happens that you are stuck with an prehistoric shared PHP/MYSQL server that can offer you only FTP access.

If this is the scene and your project it's fat, keeping the server in sync can be very tedious. Well, FTP clients come in your help with tools to compare files before update but the process can be very slow (especially if you play with composer and your file date/time mod time is not trustable).

Lift can speed up your "old school" project deployment by keeping a local index of remote files status. 
When the local index is not trustable (remote files changed by other way ecc.) Lift can rescan remote server using a PHP script with great time benefits.

## Install and Configure

### Download the phar on your project root

Download from this url: https://github.com/FbN/lift/raw/master/lift.phar and save it in your project root

```
myproject> curl -O https://github.com/FbN/lift/raw/master/lift.phar
```

### Test it

```
myproject> php lift.phar
```

If all is working you will see the lift help.

### Configure

Now you have Lift ready for use but first you have to configure it.

Create a json file named lift.json in your project root

```
> vi lift.json
```

Copy this json and edit it to feet your server and needs

```javascript
{
    "hosts": {
        "prod": {
        	"host": "mybudgetphpserver.com",
        	"username": "myftpuser",
        	"password": "myftppassword",
        	"folder": "/httpdocs",
        	"url": "http://mybudgetphpserver.com",
        	"remote-script-name": "_r.php"
        },
        "staging": {
        	"host": "192.168.1.1",
        	"username": "myftpuser",
        	"password": "myftppassword",
        	"folder": "/httpdocs",
        	"url": "http://testserver.lan",
        	"remote-script-name": "_r.php"
        }
    },
    "defaut-host": "prod",
    "ignore": [
    	"/.",
    	"^/lift."
    	"^/composer."
    ]
}
```

In this JSON sample we have declared two servers to sync. When you call lift if you not specify the server to use it will choose the one specified in by "default-host" options.

Server configuration is obvious (host, login, password bla bla bla). In "folder" you have to specify the web root folder, typically is "/httpdocs". 

"remote-script-name" is the name Lift give to the script it upload for remote scan. It will uploaded, called prefixing it with the url you write on "url" and than delete it.

Last there is the ignore section. There you configure rules to ignore files that not must be uploaded.

The rules are very simple, by examples:

```
^/lift.
```
Ignore any file path starting with "/lift.". So a file named "lift.json" or "lift.phar" in your project root will be ignored.

```
.tmp$
```
Will ignore any file path ending with ".tmp"

```
_inc.
```
Will ignore any file path containing '_inc.'

N.B. By file path Lift assume an absolute path starting from your project root.

## Use it

Simply run

```
> php lift.phar sync --remote-index
```

Lift will reindex the remote FTP server content uploading a tempory PHP script and than will upload only the changed files. The remote indexing is not necessary every time. If you are sure noone have changed the remote files afer your last sync you can run it withoute the "--remote-index" option getting a better performance.

Other options upported are:

```
 --defaut-host (-H)    Host name to upload.
 --remote-index        Dom't trust index. Remote check files status.
 --pretend (-p)        Pretend, do nothing.
 --check-time          Lift compare files by md5 checksums. If you can trust your files modification time, you can                           speedup the upload. Can be used with check-size.
 --list-new            New files
 --list-modified       Changed files
```

## License
MIT License http://opensource.org/licenses/MIT

## The End
Lift is in embrional status. It seems to work but a lot of work can be done do improve It. So star the page and keep an eye on it :).

Fabiano Taioli
ftaioli@gmail.com








