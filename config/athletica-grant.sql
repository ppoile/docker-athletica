UPDATE mysql.user SET Super_Priv='Y' WHERE user='athletica' AND host='%';
GRANT ALL PRIVILEGES ON athletica_liveresultate.* TO 'athletica'@'%';
