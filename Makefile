test: ugly

ugly: pull

DEPS = \
	deps \
	deps/casperjs \
	deps/test_sites.php \
	deps/jsbn-min.js

pull: ${DEPS}
	cd deps/jsbn-min.js && git pull origin
	cd deps/test_sites.php && git pull origin
	cd deps/casperjs && git pull origin

deps/casperjs:
	git clone https://github.com/n1k0/casperjs.git deps/casperjs

deps/test_sites.php:
	git clone https://github.com/unframed/test_sites.php.git deps/test_sites.php

deps/jsbn-min.js:
	git clone https://github.com/laurentszyster/jsbn-min.js deps/jsbn-min.js

deps:
	mkdir deps

clean:
	rm deps/* -rf

install:
	sudo apt-get install \
		wget curl zip unzip zipmerge git python php5 \
		apache2 libapache2-mod-php5 \
		nginx php5-fpm php5-mysql \
		mysql-client mysql-server \
		# wheezy-backports : nodejs nodejs-legacy
		# sid: nodejs npm phantomjs
	sudo mysql_secure_installation
	curl --insecure https://www.npmjs.org/install.sh | sudo sh
	sudo npm install uglify-js -g
