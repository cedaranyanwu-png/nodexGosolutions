# User File Manager Module

This module owns the Manage Files presentation and module-specific client assets.
The legacy dashboard tab remains a compatibility adapter and includes `view.php`
so existing tab routing and JavaScript selectors continue to work.

File reads, writes, uploads, ZIP extraction, rename, copy, move, delete, and
workspace authorization remain in the existing API and service layer. Do not put
filesystem authorization inside the view.
