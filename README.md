# DSpace Connector

Connect an Omeka S instance to a DSpace repository, optionally importing files. The repository must be using DSpace version 4 or higher.

Information about the original item and a link back to it will be included on the imported item's page.

## Installation

See general end user documentation for [Installing a module](https://github.com/omeka/omeka-s-enduser/blob/master/modules/modules.md#installing-modules)

## Usage

### Importing

From the main navigation on the left of the admin screen, click DSpace Connector. 

1. Enter the API URL of a DSpace respository you want to import from.
*This module only works with DSpace 4 or higher*

1. Click either 'Get Collections' or 'Get Communities'. When the data is loaded, click the collection you want to import.

1. Choose whether to import files.

1. Leave a comment about the import. This will appear on the `Past Imports` page, so you have a record of why you did the import (or any other information). Optional.

1. Choose an Item Set to assign imported items to. Item Sets must be created first. Optional.

1. Hit Submit.

### Undoing imports

Click `Dspace Connector`, then the new link for `Past Imports`. Check the boxes for the imports you want to undo, then submit.

