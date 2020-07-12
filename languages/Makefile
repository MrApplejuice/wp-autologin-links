.PHONY: all

all: pkg_autologin-en_US.mo

template.pot: ../autologin-links.php
	xgettext --keyword -k__ -k_e -o $@ $^

%.po: template.pot
	msgmerge -U $@ $^

pkg_autologin-%.mo: %.po
	msgfmt -o $@ $^

	