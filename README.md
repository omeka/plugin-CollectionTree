Collection Tree plugin for Omeka Classic
==================================

This plugin for Omeka Classic gives administrators the ability to create a hierarchical tree of their collections. A collection can have at most one parent collection, but a collection may have multiple child collections.

Installation
------------

This plugin has no requirements or dependencies. 

Uncompress files and rename plugin folder "CollectionTree". Then install it like any other Omeka plugin and follow the config instructions.

Configuration
------------

You can configure the plugin to adjust the display by checking the following options:

**Order alphabetically**: This setting orders the Collection Tree alphabetically, but does not affect the order of the collections browse page. 

**Browse root-level collections only**: This setting limits the public collections browse page so it only includes top-level (parent) collections and does not show subcollections (any collections nested inside others).

**Show subcollections items**: This setting includes all of the items from the subcollections in the list of items on the parent collections' show page. For example, a top-level collection with 10 items contains another collection that itself has 29 items. Check this to make the top-level collection appear to contain 39 items instead.

**Expand search to include subcollection items by default**: This setting will mean that a search performed inside a top-level collection will also look through all the items of its subcollections.

License
-------

This plugin is published under [GNU/GPL].

This program is free software; you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation; either version 3 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT
ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
FOR A PARTICULAR PURPOSE. Use it at your own risk. See the GNU General Public License for more
details.

You should have received a copy of the GNU General Public License along with
this program; if not, write to the Free Software Foundation, Inc.,
51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.

Copyright
---------



# Copyright

Collection Tree is Copyright © 2016-present [Corporation for Digital Scholarship](https://digitalscholar.org), 
Vienna, Virginia, USA
2007-2016 [Roy Rosenzweig Center for History and New Media](https://rrchnm.org)
2014 Daniel Berthereau

The Corporation for Digital Scholarship distributes the Omeka source code under the GNU General Public License, version 3 (GPLv3). The full text of this license is given in the license file.

The Omeka name is a registered trademark of the Corporation for Digital Scholarship.

Third-party copyright in this distribution is noted where applicable.

All rights not expressly granted are reserved.

- Omeka: [https://omeka.org]
- Collection Tree: [https://github.com/Omeka/plugin-CollectionTree]
- Collection Tree user manual: [https://omeka.org/classic/docs/Plugins/CollectionTree/]
- Collection Tree issues: [https://github.com/Omeka/plugin-CollectionTree/issues]
- GNU/GPL: [https://www.gnu.org/licenses/gpl-3.0.html]
- Daniel-KM: [https://github.com/Daniel-KM]
