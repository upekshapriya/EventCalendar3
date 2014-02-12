# Copyright (c) 2006, 2008, Alex Tingle.
# 
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; either version 2
# of the License, or (at your option) any later version.
# 
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# 
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA


# Generates gettext translation file.
# Just drop .po files into the languages directory and make will do the rest.   

PHP_FILES := $(wildcard *.php) $(wildcard */*.php)
PO_FILES := $(wildcard languages/ec3-*.po)
MO_FILES := $(patsubst %.po,%.mo,$(PO_FILES))

# EventCalendar's PO template
POT := languages/ec3.pot

# Working space - The shell command creates this directory.
TEMPDIR := $(shell mktemp -t -d eventcalendar.XXXXXXXXXXXXX)
# Temporary files, filtered for gettext calls in the 'ec3' domain.
XPHP_FILES := $(patsubst %,$(TEMPDIR)/%,$(PHP_FILES))

# xgettext generates a .pot template from souce code.
XGETTEXT := xgettext
XGETTEXT_OPTIONS := \
 --default-domain=ec3 \
 --language=php \
 --keyword=_x_ \
 --keyword=_y_:1,2 \
 --from-code=UTF-8 \
 --msgid-bugs-address='eventcalendar@firetree.net' \

.PHONY: all
all: $(POT) $(MO_FILES) $(TEMPDIR)/delete

$(MO_FILES): %.mo: %.po
	@echo "MSGFMT:   $@"
	msgfmt -o$@ $<

$(PO_FILES): %: $(POT)
	@echo "MSGMERGE: $@"
	msgmerge -U $@ $(POT)
	touch $@

$(POT): $(XPHP_FILES) languages/pot.sed
	@echo "XGETTEXT: $@"
	cd $(TEMPDIR) && \
	$(XGETTEXT) $(XGETTEXT_OPTIONS) -o- $(PHP_FILES) \
	| sed -f $(CURDIR)/languages/pot.sed \
	> $(CURDIR)/$@

.INTERMEDIATE: $(XPHP_FILES)
$(XPHP_FILES): $(TEMPDIR)/%: %
	@echo "SED_FILTER: $<"
	mkdir -p $(@D)
	sed \
	 -e "s/_[_e]\((.*,['\"]ec3['\"])\)/_x_\1/" \
	 -e "s/__ngettext\((.*,['\"]ec3['\"])\)/_y_\1/" \
	 $< > $@

# Force the temporary directory to be deleted when everything is done.
$(TEMPDIR)/delete:
	rm -rf $(TEMPDIR)

.PHONY: clean
clean: $(TEMPDIR)/delete
	rm -f $(POT)
	rm -f $(MO_FILES)

.SILENT:
.DELETE_ON_ERROR:
