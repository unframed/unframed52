test: ugly

ugly: pull

pull: deps
	cd deps/jsbn-min.js && git pull origin
	cd deps/fragment.js && git pull origin
	cd deps/unframed.js && git pull origin
	cd deps/parsedown && git pull origin

DEPS = \
	deps/unframed.js \
	deps/fragment.js \
	deps/jsbn-min.js \
	deps/parsedown

deps: $(DEPS)

deps/jsbn-min.js:
	git clone https://github.com/laurentszyster/jsbn-min.js deps/jsbn-min.js

deps/unframed.js:
	git clone https://github.com/laurentszyster/unframed.js deps/unframed.js

deps/fragment.js:
	git clone https://github.com/laurentszyster/fragment.js deps/fragment.js

deps/parsedown:
	git clone https://github.com/erusev/parsedown deps/parsedown

clean:
	cp php/templates/init.php www/index.html
	rm sql/*.db -f
	rm www/doc -rf

