# Copyright (c) 2006, 2007  Alex Tingle.  $Revision: 278 $
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


# Fills in some details in the ec3.pot file.   
1,/^$/ {
  s/SOME DESCRIPTIVE TITLE/Event Calendar plug-in for WordPress/
  s/YEAR THE PACKAGE.S COPYRIGHT HOLDER/2006, 2007  Alex Tingle/
  s/PACKAGE/wpcal/
  s/FIRST AUTHOR <EMAIL@ADDRESS>, YEAR./Alex Tingle <alex DOT ec3pot AT firetree.net>, 2005./
  s/VERSION/3.2.beta3/
  s/CHARSET/UTF-8/
}
