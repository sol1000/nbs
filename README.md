# Web Challenge - Sungok Lim

## Challenge Details
https://gist.github.com/nextbigsound/319f556706936c6aff39f5083055965d

## Server Environment
* [Download](https://www.apachefriends.org/download.html)  XAMPP 7.2.2 / PHP 7.2.2 and install

## Web
##### Update apache config to enable vhost
* /Applications/XAMPP/etc/httpd.conf
```
Include etc/extra/httpd-vhosts.conf
```
##### Add a virtual host
* /Applications/XAMPP/etc/extra/httpd-vhosts.conf
```
<VirtualHost *:80>
    DocumentRoot "/Users/sungok/git/nextbigsound/solution"
    ServerName nextbigsound.solution
    RewriteEngine On
</VirtualHost>
<Directory "/Users/sungok/git/nextbigsound/solution">
    Options Indexes FollowSymLinks ExecCGI Includes
    AllowOverride All
    Require all granted
</Directory>
```
> Please pick a folder that is most convenient for you. Just make it sure that all the folders to the final folder must have permissions more than 755. 


#### Define domain in hosts file
* /etc/hosts
```
127.0.0.1 nextbigsound.solution
```
#### Copy the source code
* Extract `solution.zip` file. A folder will be extracted. Then, locate your web document root and replace it with the extracted folder.

#### Create .env file in the root folder
```
DB_HOST=localhost
DB_PORT=3306
DB_NAME=nextbigsound
DB_USER=root
DB_PASS=
```

#### Create .htaccess file in the root folder
```
RewriteEngine On
RewriteRule ^events/([^/]+)/stats index.php?c=events&a=getWeeklyEvents&artistId=$1 [L,QSA]
RewriteRule ^events/total index.php?c=events&a=getTotalEvents [L,QSA]
RewriteRule ^events/matrix index.php?c=events&a=getDailyMatrix [L,QSA]
```

## MySQL

Create a MySQL database with the infomation in the .env file:
```
DB_HOST=localhost
DB_PORT=3306
DB_NAME=nextbigsound
DB_USER=root
DB_PASS=
```
> Or you can just update the `.env` file if you already have a test database.

#### Import data

* Import [nextbigsound_2018-03-12.sql](http://hancube.com/nextbigsound_2018-03-12.sql.zip) into your database.

## Challenge Result
### Web Challenge #1
#### Wrapping API
###### Weekly Events
* [/events/356/stats?startDate=2016-01-01&endDate=2017-12-31](http://nextbigsound.solution/events/356/stats?startDate=2016-01-01&endDate=2017-12-31)
* [/events/143/stats?startDate=2016-01-01&endDate=2017-12-31](http://nextbigsound.solution/events/143/stats?startDate=2016-01-01&endDate=2017-12-31)
* [/events/2468/stats?startDate=2016-01-01&endDate=2017-12-31](http://nextbigsound.solution/events/2468/stats?startDate=2016-01-01&endDate=2017-12-31)
> This API directly calls original API each time.

#### Enhanced Wrapping API

By adding `cache=1`, you can cache the original API result into the database. It will reduce the traffics to the original API, and show the fast result since it's already calculated. When you request the data that is not in the database, my API will send a request for the data to the original API, then insert the result into the database. It will take some time for the data to show the first time. It will, however, take no time for the data to show the second time on. If this was an actual service in a real world environment and I had a full access to the database, then I would run some cron jobs periodically to generate the reports on the server side. 
* [/events/356/stats?startDate=2017-01-01&endDate=2017-12-31&cache=1](http://nextbigsound.solution/events/356/stats?startDate=2017-01-01&endDate=2017-12-31&cache=1)
* [/events/143/stats?startDate=2017-01-01&endDate=2017-12-31&cache=1](http://nextbigsound.solution/events/143/stats?startDate=2017-01-01&endDate=2017-12-31&cache=1)
* [/events/2468/stats?startDate=2017-01-01&endDate=2017-12-31&cache=1](http://nextbigsound.solution/events/2468/stats?startDate=2017-01-01&endDate=2017-12-31&cache=1)
> I limited the date range to one year just in case my API takes more time to load than the apache max_execution_time while it's storing the result into the database.


#### 2 more Additional APIs for the Graphs

I built 2 more APIs to generate matrix shape of the API result for the graphs. I limited the date range to one year for those APIs too.
###### Daily Events API - for the Path Graph.
* [/events/matrix/?artistIds=356&startDate=2017-01-01&endDate=2017-12-31](http://nextbigsound.solution/events/matrix/?artistIds=356&startDate=2017-01-01&endDate=2017-12-31)
* [/events/matrix/?artistIds=356,143&startDate=2017-01-01&endDate=2017-12-31](http://nextbigsound.solution/events/matrix/?artistIds=356,143&startDate=2017-01-01&endDate=2017-12-31)
* [/events/matrix/?artistIds=356,143,2468&startDate=2017-01-01&endDate=2017-12-31](http://nextbigsound.solution/events/matrix/?artistIds=356,143,2468&startDate=2017-01-01&endDate=2017-12-31)

###### Total events by event type - for the Bubble Graph.
* [/events/total/?artistIds=356&startDate=2017-01-01&endDate=2017-12-31](http://nextbigsound.solution/events/total/?artistIds=356&startDate=2017-01-01&endDate=2017-12-31)
* [/events/total/?artistIds=356,143&startDate=2017-01-01&endDate=2017-12-31](http://nextbigsound.solution/events/total/?artistIds=356,143&startDate=2017-01-01&endDate=2017-12-31)
* [/events/total/?artistIds=356,143,2468&startDate=2017-01-01&endDate=2017-12-31](http://nextbigsound.solution/events/total/?artistIds=356,143,2468&startDate=2017-01-01&endDate=2017-12-31)
> If you want to test the APIs with another artist, please insert artist_id and name into `artists` table first.


### Web Challenge #2
#### Bubble Graph - Total Events by event type
* [/d3/events_bubble/?artistIds=356,143&startDate=2016-01-01&endDate=2016-12-31](http://nextbigsound.solution/d3/events_bubble/?artistIds=356,143&startDate=2016-01-01&endDate=2016-12-31)
* [/d3/events_bubble/?artistIds=356,143,2468&startDate=2016-01-01&endDate=2016-12-31](http://nextbigsound.solution/d3/events_bubble/?artistIds=356,143,2468&startDate=2016-01-01&endDate=2016-12-31)

#### Path Graph - Daily Events
* [/d3/events_path/?artistIds=356,143&startDate=2016-01-01&endDate=2016-05-31](http://nextbigsound.solution/d3/events_path/?artistIds=356,143&startDate=2016-01-01&endDate=2016-05-31)
* [/d3/events_path/?artistIds=356,143,2468&startDate=2016-01-01&endDate=2016-05-31](http://nextbigsound.solution/d3/events_path/?artistIds=356,143,2468&startDate=2016-01-01&endDate=2016-05-31)

#### Graph with Dots - Timeserise Data 
* [/d3/timeseries_dot/?artistId=143&startDate=2016-01-01&endDate=2016-12-31](http://nextbigsound.solution/d3/timeseries_dot/?artistId=143&startDate=2016-01-01&endDate=2016-12-31)
* [/d3/timeseries_dot/?artistId=18&startDate=2016-01-01&endDate=2016-12-31](http://nextbigsound.solution/d3/timeseries_dot/?artistId=18&startDate=2016-01-01&endDate=2016-12-31)
* [/d3/timeseries_dot/?artistId=456&startDate=2016-01-01&endDate=2016-12-31](http://nextbigsound.solution/d3/timeseries_dot/?artistId=456&startDate=2016-01-01&endDate=2016-12-31)
> This graph shows the data directly from the original API
