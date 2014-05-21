test: ugly

ugly: pull

pull: deps
	cd deps/jsbn-min.js && git pull origin

DEPS = \
	deps/jsbn-min.js

deps: $(DEPS)

deps/jsbn-min.js:
	git clone https://github.com/laurentszyster/jsbn-min.js deps/jsbn-min.js

clean:
	cp php/templates/init.php www/index.html
	rm sql/*.db -f
	rm www/doc -rf

install:
	sudo apt-get install \
		python nginx apache2 php5-fpm libapache2-mod-php5