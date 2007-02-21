# Horde .mo files makefile
#
# $Horde: horde/po/Makefile,v 1.2.2.3 2002/05/20 17:36:22 jan Exp $
#

LANGS:=$(shell ls *.po|sed -e s/\.po$$//)

all: banana.pot ${LANGS:=.lang}

clean:
	rm -f *.po~ *.lang

banana.pot:
	@echo Parsing Tree for new messages
	@echo
	@echo "<?php " > template.php
	@grep "|b" ../banana/templates/*.tpl | sed 's/\(.*\)\("[^"]*"\)|b\(.*\)/$var = _b_(\2);/g' >> template.php
	@echo "?>" >> template.php
	@xgettext --from-code=UTF-8 -j -k_b_ -o banana.pot ../banana/*.php ./template.php
	@rm template.php

%.lang: banana.pot ../banana/*.php %.po
	@echo Generating $(@:.lang=.po)
	@echo -n '    '
	@mkdir -p ../locale/$(@:.lang=)/LC_MESSAGES/ 
	@msgmerge -U $(@:lang=po) $< 2> /dev/null
	@msgfmt --statistics -c -v -o ../locale/$(@:.lang=)/LC_MESSAGES/banana.mo $(@:lang=po)
	@echo
	@rm -f $(@:lang=po~)
	@touch $@


