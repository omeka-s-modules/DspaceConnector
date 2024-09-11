# DSpace Connector

Connect an Omeka S instance to a DSpace repository, optionally importing files. The repository must be using DSpace version 5.6 or higher. Separate instructions apply if the DSpace version is 7 or higher. 
This connection relies on the DSpace installation's public API. If this is not accessible, you will not be able to import or update items. 

The connector will perform a one-time import, and also maintain the connection to the original items, allowing you to refresh the import with updated metadata and media from the source when desired. The imported Omeka items will include links back to the DSpace source material. 

To create a connection you will need the following information:

- DSpace site URL for the repository - the entire URL, including the "http://". This should be the base DSpace repository URL with no `/rest/` or `/server/` information added to the end.
- Endpoint for the API. Provide `rest` for DSpace versions lower than 7, and `server/api` for versions 7 and above.
- A limit or maximum number of results to retrieve at once. When importing a large number of DSpace items, you can flood the remote server; setting a batch limit here can ensure the import runs smoothly.
- Set whether you wish to test the import by using the limit number, above, rather than the full collection available at the endpoint. 

At the next stage you can choose which DSPace collections and communities to import according to what is available by the API. You can also set where to put the imported items (item sets and/or sites), and whether to exclude particular metadata fields. 

See the [Omeka S user manual](http://omeka.org/s/docs/user-manual/modules/dspaceconnector/) for user documentation.

## Copyright
DSpaceConnector is Copyright © 2015-present Corporation for Digital Scholarship, Vienna, Virginia, USA http://digitalscholar.org

The Corporation for Digital Scholarship distributes the Omeka source code
under the GNU General Public License, version 3 (GPLv3). The full text
of this license is in the license file.

The Omeka name is a registered trademark of the Corporation for Digital Scholarship.

Third-party copyright in this distribution is noted where applicable.

All rights not expressly granted are reserved.

