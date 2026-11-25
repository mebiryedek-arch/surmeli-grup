<?php
/**
 * Intentionally preserves all Sürmeli Grup data and settings on uninstall.
 * Data removal, if ever required, must be an explicit separately confirmed operation.
 */
defined( 'WP_UNINSTALL_PLUGIN' ) || exit;
// No database tables, options, users, pages, or uploaded files are deleted.
