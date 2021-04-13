#! /bin/bash

docker run -dit --restart always -p 3007:3306 -e "MYSQL_ROOT_HOST=%" -e "MYSQL_ALLOW_EMPTY_PASSWORD=true" --name cv-mysql mysql/mysql-server:5.7

# Then, connect with `mysql -h 127.0.0.1 -P 3007 -u root`
# I wonder if we could more programatically do this.
echo "Waiting half a minute for mysql server to start up..."
sleep 35

if ! grep "CREATE DATABASE" ./migrate/schema.sql # use seperate sql script to create database bc database creation will not be included in sql dump
then 
    cat ./migrate/createdatabase.sql ./migrate/schema.sql > ./migrate/tmp.sql
    mv ./migrate/tmp.sql ./migrate/schema.sql
fi

docker exec -i cv-mysql mysql < migrate/schema.sql
