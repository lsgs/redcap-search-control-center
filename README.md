********************************************************************************
# Search Control Center

Luke Stevens, Murdoch Children's Research Institute https://www.mcri.edu.au

[https://github.com/lsgs/redcap-record-nav/](https://github.com/lsgs/redcap-record-nav/)
********************************************************************************
## Functionality

This module adds an autocomplete lookup to the Control Center menu. You can use it to search for text found on a Control Center page, see a list of matches, then navigate to the desired page by selecting the result.

### Usage

Type a search term into the text box. A list of matches will be displayed. Click on an entry in the list to navigate to that page.

<img title="search control centre" src=""/>

### Content Capture

The module provides a plugin page accessed via the "Control Center Content Capture" link in the External Modules section of the Control Center menu. This page displays the date/time and REDCap version for which Control Center page content has been captured into the system-level module settings. Note:

- The module will not work until this capture has been performed. An alert will appear above the search box to indicate this.
- A refresh of the captured content will be suggested after version upgrades. An alert will appear above the search box to indicate this.

<img title="control center content capture" src="https://redcap.mcri.edu.au/surveys/index.php?pid=14961&__passthru=DataEntry%2Fimage_view.php&doc_id_hash=3fd2ad89646e8720d0fafbcd3dd36ce10e1890a9&id=2179308&s=EFQEBHnsLmtxUoTq&page=file_page&record=19&event_id=47634&field_name=thefile&instance=1" />