test: ugly

ugly: pull

DEPS = \
	deps \
	deps/casperjs \
	deps/test_sites.php \
	deps/JSONMessage.php

pull: ${DEPS}
	cd deps/JSONMessage.php && git pull origin
	cd deps/test_sites.php && git pull origin
	cd deps/casperjs && git pull origin

deps/casperjs:
	git clone https://github.com/n1k0/casperjs.git deps/casperjs

deps/test_sites.php:
	git clone https://github.com/unframed/test_sites.php.git deps/test_sites.php

deps/JSONMessage.php:
	git clone \
		https://github.com/laurentszyster/JSONMessage.php.git \
		deps/JSONMessage.php

deps:
	mkdir deps

clean:
	rm deps/* -rf

install:
	sudo apt-get install \
		wget curl zip unzip zipmerge git python php5 php5-sqlite \
		apache2 libapache2-mod-php5 \
		nginx php5-fpm php5-mysql \
		mysql-client mysql-server \
		# wheezy-backports : nodejs nodejs-legacy
		# sid: nodejs npm phantomjs
	sudo mysql_secure_installation
	curl --insecure https://www.npmjs.org/install.sh | sudo sh
	sudo npm install uglify-js -g
