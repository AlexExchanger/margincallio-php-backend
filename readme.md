PHP Backend
==============

This part is a wrapper around C# engine. 
It is based on Yii framework and has it's standart layout, so it wont be difficult to identidy all api calls.

PHP Backend connects to it via socket with admin permissions.

PHP is able to send command "add usern #1 1billion BTC" so be careful.

It handles all requests from client, check their validity and resend to the core. 
Also it handles all cryptocurrency and fiat transactions. 
There is a repo -- bitcoind wrapper https://bitbucket.org/margincallio/bitcoind-wrapper for handling bitcoin transactions.

To be honest this part is the most buggy and undocumented. 
But the good information is that it depends only on:

-Postgresql 9.3
-PHP 5.4

Configs are in config directory. 

In the root folder are presented:

- nginx configuration
- sql schema only file
- sql dump with a sample data. To run own exchange surely you need to copy some data from "account" table.
That data can be identified with "system.*" entry.


