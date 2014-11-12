#lift
====

## What and Why

Lift is a PHP script for quick sync with FTP server.

The world is full of wonderfull tecnology that helps you deploy your project in marvelous ways: ssh tools, cloud API, docker, git deploy, bla bla bla. 

Unfortunately happens that you are stuck with an prehistoric shared PHP/MYSQL server that can offer you only FTP access.

If this is the scene and your project it's fat, keeping the server in sync can be very tedious. Well, FTP clients come in your help with tools to compare files before update but the process can be very slow (especially if you play with composer and your file date/time mod time is not trustable).

Lift can speed up your "old school" project deployment by keeping a local index of remote files status. 
When the local index is not trustable (remote files changed by other way ecc.) Lift can rescan remote server using a PHP script with great time benefits.

## Usage

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





