#!/usr/bin/python3
# -*- coding: utf-8 -*-
#pip install meteomatics
import sys
import datetime as dt
import meteomatics.api as api
import json
import sqlite3
import os

#1 = Piste CMB

class classdb:
    def __init__(self):
        self.dbfile="/var/www/html/mtomatics/db/mtomatics.db"
        self.conn = sqlite3.connect(self.dbfile)
        self.lat=None;
        self.long=None;
        self.countmto=None;
        self.countmtomonth=None;
        self.sites={}

    def get_params(self):
        r=True
        cur = self.conn.cursor()
        try:
            sql="SELECT p.id, p.site, p.lat, p.long, p.iduser, u.username,u.password FROM param AS p "
            sql+="inner join users as U on p.iduser=U.id "
            sql+="where p.enable=1 order by p.iduser DESC;"
            cur.execute(sql)
        except Exception as e:
            r=False
            print(e, " occured")
        if (r==True):
            data_list = cur.fetchall() 
            for item in data_list:
                onesite={}
                onesite["id"]=item[0]
                onesite["site"]=item[1]
                onesite["lat"]=item[2]
                onesite["long"]=item[3]
                onesite["iduser"]=item[4]
                onesite["username"]=item[5]
                onesite["password"]=item[6]
                self.sites[onesite["id"]]=onesite
        cur.close()
        self.conn.commit()
        return r
        
    def get_coord(self,idsite):
        r=True
        cur = self.conn.cursor()
        try:
            sql="SELECT lat, long FROM param WHERE id = "+str(idsite)
            cur.execute(sql)
        except Exception as e:
            r=False
            print(e, " occured")
        if (r==True):
            data_list = cur.fetchall() 
            for item in data_list:
                self.lat=item[0]
                self.long=item[1]
        cur.close()
        self.conn.commit()
        return r
    
    
    def update_mto(self,idsite,ts,t_2m,precip_1h,wind_dir_10m,wind_speed_10m,wind_gusts_10m_1h,msl_pressure,weather_symbol_1h,weather_symbol_24h,sunrise,sunset):
        r=True
        cur = self.conn.cursor()
        try:
            sql="INSERT OR REPLACE INTO mto(idsite, ts, t_2m, precip_1h, wind_dir_10m, wind_speed_10m, wind_gusts_10m_1h,msl_pressure,weather_symbol_1h,weather_symbol_24h,sunrise,sunset,dmaj) VALUES(?, ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ?)"
            cur.execute(sql,(idsite, ts,t_2m,precip_1h,wind_dir_10m,wind_speed_10m,wind_gusts_10m_1h,msl_pressure,weather_symbol_1h,weather_symbol_24h,sunrise,sunset,dt.datetime.now(dt.UTC).replace(second=0)))
        except Exception as e:
            r=False
            print(e, " occured")
        cur.close()
        self.conn.commit()
        return r

        
    def get_countmto(self,idsite):
        r=True
        cur = self.conn.cursor()
        try:
            sql="SELECT count(*) FROM mto WHERE idsite = "+str(idsite)
            cur.execute(sql)
        except Exception as e:
            r=False
            print(e, " occured")
        if (r==True):
            data_list = cur.fetchall() 
            for item in data_list:
                self.countmto=item[0]
        cur.close()
        self.conn.commit()
        return r
        
    def get_countmtomonth(self,idsite):
        r=True
        cur = self.conn.cursor()
        try:
            sql="SELECT count(*) FROM mtomonth WHERE idsite = "+str(idsite)
            cur.execute(sql)
        except Exception as e:
            r=False
            print(e, " occured")
        if (r==True):
            data_list = cur.fetchall() 
            for item in data_list:
                self.countmtomonth=item[0]
        cur.close()
        self.conn.commit()
        return r
        
    def update_stat_month(self,idsite):
        r=True
        cur = self.conn.cursor()
        try:
            sql="DELETE FROM mtomonth WHERE idsite = "+str(idsite)
            cur.execute(sql)
        except Exception as e:
            r=False
            print(e, " occured")
        if (r==True):
            sql="INSERT INTO mtomonth (idsite,ANNEE,MOIS,TEMP_MIN,TEMP_MAX,TEMP_AVG,SPEED_MIN,SPEED_MAX,SPEED_AVG,GUST_MAX,GUST_AVG,PRESSURE,PRECIP_TOTAL,WD1,WD1C,WD1S,WD2) "
            sql+="SELECT idsite, cast(strftime('%Y',datetime(ts,'localtime')) as integer)  as ANNEE, "
            sql+="cast(strftime('%m',datetime(ts,'localtime')) as integer) as PERIODE, "
            sql+="round(min(t_2m),2) as TEMP_MIN, "
            sql+="round(max(t_2m),2) as TEMP_MAX, " 
            sql+="round(avg(t_2m),2) as TEMP_AVG, "
            sql+="round(min(wind_speed_10m),2) as SPEED_MIN, "
            sql+="round(max(wind_speed_10m),2) as SPEED_MAX, "
            sql+="round(avg(wind_speed_10m),2) as SPEED_AVG, " 
            sql+="round(max(wind_gusts_10m_1h),2) as GUST_MAX, "
            sql+="round(avg(wind_gusts_10m_1h),2) as GUST_AVG, "
            sql+="round(avg(msl_pressure),0) AS PRESSURE, " 
            sql+="round(sum(precip_1h),0) as PRECIP_TOTAL, " 
            sql+="round(sum(wind_speed_10m * wind_dir_10m),3) as WD1, "
            sql+="round(sum(wind_speed_10m * ( cos( wind_dir_10m * pi()/180.0 ))),3) as WD1C, "
            sql+="round(sum(wind_speed_10m * ( sin( wind_dir_10m * pi()/180.0 ))),3) as WD1S, "
            sql+="round(sum(wind_speed_10m),3) as WD2 " 
            sql+="from mto " 
            sql+="WHERE idsite = "+str(idsite)+" and strftime('%Y%m',datetime(ts,'localtime'))<strftime('%Y%m',datetime('now','localtime')) " 
            sql+="group by idsite,strftime('%Y',datetime(ts,'localtime')) , cast(strftime('%m',datetime(ts,'localtime')) as integer) "
            try:
                cur.execute(sql)
            except Exception as e:
                r=False
                print(e, " occured")
        cur.close()
        self.conn.commit()
        return r
        
    def update_trt(self,idsite):
        r=True
        cur = self.conn.cursor()
        try:
            sql="UPDATE param SET LASTTRT=current_timestamp WHERE id = "+str(idsite)
            cur.execute(sql)
        except Exception as e:
            r=False
            print(e, " occured")
        cur.close()
        self.conn.commit()
        return r


    def close(self):
        self.conn.close()

now=dt.datetime.now()
print("Il est",now.hour,"H",now.minute,"Local")
if ((now.hour>=23) or (now.hour<5)) and ((now.hour/3.0-now.hour//3)!=0):
    print("Pas de traitement entre 23h et 5h, sauf si le nombre d'heure est un multiple de 3")
    sys.exit(0)
else:
    print("Le traitement va démarrer")

parameters = ['t_2m:C', 'precip_1h:mm', 'wind_dir_10m:d', 'wind_speed_10m:ms', 'wind_gusts_10m_1h:ms', 'msl_pressure:hPa','weather_symbol_1h:idx', 'weather_symbol_24h:idx', 'sunrise:sql', 'sunset:sql']
model = 'mix'
startdate = dt.datetime.now(dt.UTC).replace(minute=0, second=0, microsecond=0)
enddate = startdate + dt.timedelta(days=1)
interval = dt.timedelta(hours=1)


db=classdb()
if (db.get_params()==False):
    print("Une erreur s'est produite")
    db.close()
    sys.exit(0)
    
print("Sites disponibles")
print(db.sites)


for item in db.sites:
    print("-------------------")
    idsite=db.sites[item]["id"]
    site=db.sites[item]["site"]
    lat=db.sites[item]["lat"]
    long=db.sites[item]["long"]
    username=db.sites[item]["username"]
    password=db.sites[item]["password"]
    #print(db.lat, db.long)
    print("Interrogation API pour ["+str(idsite)+"] "+str(site)+" "+str(lat)+", "+str(long)+", "+str(username)+", "+str(password))
    df = api.query_time_series([(lat, long)], startdate, enddate, interval, parameters, username, password, model=model)
    js = json.loads(df.to_json(orient = 'table'))
    i=0
    for row in js["data"]:
        if db.update_mto(idsite,row['validdate'],row['t_2m:C'],row['precip_1h:mm'],round(row['wind_dir_10m:d'])%360,row['wind_speed_10m:ms'],row['wind_gusts_10m_1h:ms'], round(row['msl_pressure:hPa']) ,round(row['weather_symbol_1h:idx']) ,round(row['weather_symbol_24h:idx']), row['sunrise:sql'], row['sunset:sql']):
            i+=1

    if (db.update_stat_month(idsite)==False):
        print("Une erreur s'est produite")
    elif (db.get_countmto(idsite)==False):
        print("Une erreur s'est produite")
    elif (db.get_countmtomonth(idsite)==False):
        print("Une erreur s'est produite")

    else: 
        db.update_trt(idsite)
        print("Terminé, "+str(i)+" lignes inserées dans la table mto pour le site "+str(idsite))
        print("La table MTo contient maintenant "+str(db.countmto)+" lignes pour le site "+str(idsite))
        print("La table MToMonth contient maintenant "+str(db.countmtomonth)+" lignes pour le site "+str(idsite))
db.close()
sys.exit(0)
