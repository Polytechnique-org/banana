# definitions

VERSION=$(shell grep VERSION Changelog | head -1 | sed -e "s/VERSION //;s/\t.*//")
PKG_DIST = banana-$(VERSION)

PKG_FILES = AUTHORS Changelog COPYING README Makefile TODO

PKG_DIRS = banana po

VCS_FILTER = ! -name .arch-ids ! -name CVS

# global targets

build: pkg-build

dist: clean pkg-dist

clean:
	rm -rf locale banana/include/banana.inc.php

%: %.in Makefile
	sed -e 's,@VERSION@,$(VERSION),g' $< > $@


# banana package targets

pkg-build: banana/banana.inc.php
	make -C po

pkg-dist: pkg-build
	rm -rf $(PKG_DIST) $(PKG_DIST).tar.gz
	mkdir $(PKG_DIST)
	cp -a $(PKG_FILES) $(PKG_DIST)
	for dir in `find $(PKG_DIRS) -type d $(VCS_FILTER)`; \
	do \
          mkdir -p $(PKG_DIST)/$$dir; \
	  find $$dir -type f -maxdepth 1 -exec cp {} $(PKG_DIST)/$$dir \; ; \
	done
	tar czf $(PKG_DIST).tar.gz $(PKG_DIST)
	rm -rf $(PKG_DIST)



.PHONY: build dist clean pkg-build pkg-dist lib-build lib-dist

