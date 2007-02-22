CFLAGS=-O2 -g -fstrict-aliasing -Wall -Wextra -Werror -Wchar-subscripts -Wundef -Wshadow -Wcast-align -Wwrite-strings -Wsign-compare -Wunused -Wno-unused-parameter -Wuninitialized -Winit-self -Wpointer-arith -Wredundant-decls -Wformat-nonliteral -Wno-format-zero-length -Wno-format-y2k -Wmissing-format-attribute -Wbad-function-cast -Wstrict-prototypes -Wmissing-prototypes -Wmissing-declarations -Wnested-externs -Wdeclaration-after-statement

all: mbox-helper Makefile

mbox-helper: mbox-helper.o

mbox-helper.o: mbox-helper.c

clean:
	-rm *.o
	-rm mbox-helper
