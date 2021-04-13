#!/bin/bash

# Read our password from our secret file.
if [ -f "db.config" ] || [ -f "/var/secret/db.config" ]
then
	# By placing the loading of the global file first,
	# we are making the local file take precedence over
	# the global file.
	if [ -f "/var/secret/db.config" ]
	then
		. /var/secret/db.config
	fi

	if [ -f "db.config" ]
	then
		. db.config
	fi
fi

# Prepare a FQDN into a domain name.
# On Linux, dnsdomainname can be used,
# but using cut allows for backwards-compatibility
# with Mac OS.
production_domain1="environmentaldashboard.org"
production_domain2="communityhub.cloud"
domain=`cut -f 2- -d . <<< $HOSTNAME`

if [ "$domain" = "$production_domain1" ] || [ "$HOSTNAME" = "$production_domain1" ] || \
	 [ "$domain" = "$production_domain2" ] || [ "$HOSTNAME" = "$production_domain2" ]
then
	# live server:
	# Get the computer name.
	computer=`cut -f 1 -d . <<< $HOSTNAME`

	docker run -dit -p 3001:80 --restart always \
	-v /var/www/uploads/CV_Media/images/:/var/www/uploads/CV_Media/images/ \
	-v $(pwd)/src:/var/www/html/src \
	-v /etc/opendkim/keys/environmentaldashboard.org/mail.private:/opendkim/mail.private \
	-e "MYSQL_HOST=159.89.232.129" -e "MYSQL_DB=community_voices" -e "MYSQL_PORT=$port" -e "MYSQL_USER=$user" -e "MYSQL_PASS=$pass" \
	-e SERVER=`hostname` -e APP_ENV=production \
	-e API_URL=https://www.environmentaldashboard.org/community-voices/api \
	-e DOCKER_CONTAINER_NAME=PROD_CV \
	--name PROD_CV community-voices
else
	# local machine:
	docker run -dit -p 3001:80 --restart always \
	-v $(pwd)/CV_Media/images/:/var/www/uploads/CV_Media/images/ \
	-v $(pwd)/src:/var/www/html/src \
	-v $(pwd)/migrate:/var/www/html/migrate \
	-v $(pwd)/composer.json:/var/www/html/composer.json \
	-v $(pwd)/composer.lock:/var/www/html/composer.lock \
	-e "MYSQL_HOST=cv-mysql" -e "MYSQL_DB=community_voices" -e "MYSQL_PORT=3306" -e "MYSQL_USER=root" \
	--link cv-mysql:cv-mysql \
	-e SERVER=`hostname` -e APP_ENV=development \
	-e API_URL=http://localhost:80/community-voices/api \
	-e DOCKER_CONTAINER_NAME=LOCAL_CV \
	--name LOCAL_CV community-voices
fi
