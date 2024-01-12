# MtoMatics
Get weather data, and history



MtoMatics is a web app wrinting in php, javascript, css , sql and python.
It needs an account from api.meteomatics.com.
A python script downloads data using meteomatics.com's api every hour.
Data are stores in an sqlite3 database.
And a website shows weather situation, prediction and history.
MtoMatics can use multiple accounts and multiple sites.

# What you needs ?
Go to api.meteomatics.com and create an account
- check that php is installed, sqlite extension enable
- install sqlite and meteomatics.api python's api
- put files and folder in a vitrualhost doccument root
Open /db/mtomatics.db
- go to users table and store your api user/password.
- go to param table and insert / update weather locations that you need
- Create a task (with crontab for exemple) to launch /db/mtomatics.py every hour
- Open your virtualhost http adress.
- That's all. you can check .htaccess files to rotect your database from being downloaded


  

