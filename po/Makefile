# Horde .mo files makefile
#
# $Horde: horde/po/Makefile,v 1.2.2.3 2002/05/20 17:36:22 jan Exp $
#

MSGFMT = msgfmt --statistics -c -v -o
MSGFMTSOL = msgfmt -v -o
MSGMERGE = msgmerge -U
LANGS:=$(shell ls *.po|sed -e s/\.po$$//)
APPLICATION = banana

all: banana.pot ${LANGS:=.lang}
	if test "${OSTYPE}" = "solaris"; then \
		echo "You'll probably get some warnings on Solaris. This is normal."; \
	fi;

banana.pot: ../banana/*.php
	xgettext --from-code=iso-8859-15 -j -k_b_ -o banana.pot $<

%.lang: ../locale/%/LC_MESSAGES/banana.mo %.po
	@echo Updating for lang %


%.po: banana.pot
	${MSGMERGE} $@ $<

	

build: *.po 
	for LOCALE in `ls *.po | sed 's/\.[^.]*$$//g'`; do \
		if test $${LOCALE}.po = "messages.po"; then \
			continue; \
		fi; \
		echo "Compiling locale $${LOCALE}:"; \
		if ./shtool mkdir -p ../locale/$${LOCALE}/LC_MESSAGES; then \
			if test "${OSTYPE}" = "solaris"; then \
				if ${MSGFMTSOL} ../locale/$${LOCALE}/LC_MESSAGES/${APPLICATION}.mo $${LOCALE}.po; then \
					echo "  ... done"; \
					echo; \
				else \
					echo "  ... FAILED"; \
					echo; \
				fi \
			else \
				if ${MSGFMT} ../locale/$${LOCALE}/LC_MESSAGES/${APPLICATION}.mo $${LOCALE}.po; then \
					echo "  ... done"; \
					echo; \
				else \
					echo "  ... FAILED"; \
					echo; \
				fi \
			fi; \
		else \
			echo "Could not create locale directory for $${LOCALE}."; \
		fi \
	done;
