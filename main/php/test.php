<?php
if (function_exists('imap_open')) {
    echo "IMAP is enabled.";
} else {
    echo "IMAP is NOT enabled.";
}
?>