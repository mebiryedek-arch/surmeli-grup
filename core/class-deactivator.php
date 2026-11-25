<?php
/** Deactivation must never delete business data. */
defined( 'ABSPATH' ) || exit;

final class SG_Deactivator {
    public static function deactivate(): void {
        flush_rewrite_rules();
    }
}
