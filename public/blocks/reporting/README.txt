
================================
Folder reporting/report/compile must be granted as wriable.

sudo chmod -R 770 reporting/report/compile

=================================
To disable Vendor in the Report,
We only need to disable the link in the reporting/block_reporting.php
It is around the line 27 which has been added comments.
=================================
Vendor can only see the report that they have been enrolled into. Therefore, they need to be enrolled into the courses if they want to view the report.

================================
Vendor will see course overview in the next level of selected node

*****
eric@mindatlas.com