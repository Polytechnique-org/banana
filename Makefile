# definitions

VERSION=$(shell grep VERSION Changelog | head -1 | sed -e "s/VERSION //;s/\t.*//")
PKG_DIST = banana-$(VERSION)

PKG_FILES = AUTHORS Changelog COPYING README Makefile TODO

PKG_DIRS = banana po css examples img

VCS_FILTER = ! -name .svn

# global targets

build: pkg-build

dist: clean pkg-dist

clean:
	rm -rf locale banana/banana.inc.php
	make -C po clean

%: %.in Makefile
	sed -e 's,@VERSION@,$(VERSION) The Bearded Release,g' $< > $@


# banana package targets

pkg-build: banana/banana.inc.php
	make -C po
	make -C po clean

pkg-dist: pkg-build
	rm -rf $(PKG_DIST) $(PKG_DIST).tar.gz
	mkdir $(PKG_DIST)
	cp -a $(PKG_FILES) $(PKG_DIST)
	for dir in `find $(PKG_DIRS) -type d $(VCS_FILTER)`; \
	do \
          mkdir -p $(PKG_DIST)/$$dir; \
	  find $$dir -type f $(VCS_FILTER) -maxdepth 1 -exec cp {} $(PKG_DIST)/$$dir \; ; \
	done
	tar czf $(PKG_DIST).tar.gz $(PKG_DIST)
	rm -rf $(PKG_DIST)



.PHONY: build dist clean pkg-build pkg-dist lib-build lib-dist

