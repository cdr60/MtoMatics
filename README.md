# MtoMatics
Get weather data, and history

![Capture d'écran 2024-01-12 232546](https://github.com/cdr60/MtoMatics/assets/104300119/7a03b3f3-8404-4d0d-9427-e645461ab4cc)
![Capture d'écran 2024-01-12 232516](https://github.com/cdr60/MtoMatics/assets/104300119/465ea6b9-b501-48e7-b608-12c6319d1f38)
![Capture d'écran 2024-01-12 232439](https://github.com/cdr60/MtoMatics/assets/104300119/f0e6fa6c-1e1c-48cc-aca1-2f3208a06407)
![Capture d'écran 2024-01-12 232449](https://github.com/cdr60/MtoMatics/assets/104300119/6629d6f4-4409-41b8-bc7f-b677a2b1ed25)

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
Open /db/mtomatics-init.db, rename it /db/mtomatics.db
- go to users table and store your api user/password.
- go to param table and insert / update weather locations that you need
- Create a task (with crontab for exemple) to launch /db/mtomatics.py every hour
- Open your virtualhost http adress.
- That's all. you can check .htaccess files to rotect your database from being downloaded


  

