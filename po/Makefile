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

clean:
	rm -f *.po~ *.lang

banana.pot: ../banana/*.php
	@echo Parsing Tree for new messages
	@echo
	@xgettext --from-code=iso-8859-15 -j -k_b_ -o banana.pot $<

%.lang: banana.pot ../banana/*.php %.po
	@echo Generating $(@:.lang=)
	@echo -n '    '
	@${MSGMERGE} $(@:.lang=) $< 2> /dev/null
	@${MSGFMT} ../locale/$(@:.lang=)/LC_MESSAGES/banana.mo $(@:.lang=)
	@echo
	@touch $@

