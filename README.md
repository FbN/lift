#lift
====

## What and Why

Lift is a PHP script for quick sync with FTP server.

The world is full of wonderfull tecnology that helps you deploy your project in marvelous ways: ssh tools, cloud API, docker, git deploy, bla bla bla. 

Unfortunately happens that you are stuck with an prehistoric shared PHP/MYSQL server that can offer you only FTP access.

If this is the scene and your project it's fat, keeping the server in sync can be very tedious. Well, FTP clients come in your help with tools to compare files before update but the process can be very slow (especially if you play with composer and your file date/time mod time is not trustable).

Lift can speed up your "old school" project deployment by keeping a local index of remote files status. 
When the local index is not trustable (remote files changed by other way ecc.) Lift can rescan remote using a PHP script with great time benefits.
