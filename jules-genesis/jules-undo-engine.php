<?php
/**
 * Jules Undo Engine (CTRL+Z System)
 *
 * Provides snapshot and rollback functionality for the Jules Genesis plugin.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class Jules_Undo_Engine {

    private $backup_dir;

    public function __construct() {
        $this->backup_dir = plugin_dir_path( __FILE__ ) . 'backups/';

        // Ensure the backups directory exists
        if ( ! file_exists( $this->backup_dir ) ) {
            mkdir( $this->backup_dir, 0755, true );
        }
    }

    /**
     * Create a backup of a file before modifying it, or note if it's a new file.
     *
     * @param string $filepath The absolute path to the file.
     * @return bool True on success.
     */
    public function snapshot( $filepath ) {
        $filename = basename( $filepath );
        $backup_path = $this->backup_dir . $filename . '.bak';

        // Save the original path inside a meta file
        file_put_contents( $this->backup_dir . 'last_target.meta', $filepath );
        $new_file_meta = $this->backup_dir . 'is_new_file.meta';

        if ( file_exists( $filepath ) ) {
            // Clean up any old new-file flag to avoid deleting existing files on undo
            if ( file_exists( $new_file_meta ) ) {
                unlink( $new_file_meta );
            }

            $copied = copy( $filepath, $backup_path );

            if ( $copied ) {
                $this->log( "[Snapshot Created] Copied {$filepath} to {$backup_path}" );
                return true;
            } else {
                $this->log( "[Error] Failed to create snapshot for {$filepath}" );
                return false;
            }
        } else {
            // If the file doesn't exist, we note it in the meta file as a new creation
            file_put_contents( $new_file_meta, '1' );
            $this->log( "[Snapshot Created] Noted creation of new file {$filepath}" );
            return true;
        }
    }

    /**
     * Restore the last modified file from its backup, or delete if it was newly created.
     *
     * @return array Status and message.
     */
    public function undo() {
        $meta_file = $this->backup_dir . 'last_target.meta';
        $new_file_meta = $this->backup_dir . 'is_new_file.meta';

        if ( ! file_exists( $meta_file ) ) {
            return array( 'success' => false, 'message' => 'No previous changes found.' );
        }

        $original_filepath = trim( file_get_contents( $meta_file ) );

        // Check if the previous action was creating a brand new file
        if ( file_exists( $new_file_meta ) ) {
            if ( file_exists( $original_filepath ) ) {
                unlink( $original_filepath );
                $this->log( "[UNDO] Deleted newly created file {$original_filepath}." );
            }
            unlink( $meta_file );
            unlink( $new_file_meta );
            return array( 'success' => true, 'message' => "Successfully undid creation of {$original_filepath}." );
        }

        // Otherwise, it was a modification, restore from backup
        $filename = basename( $original_filepath );
        $backup_path = $this->backup_dir . $filename . '.bak';

        if ( ! file_exists( $backup_path ) ) {
            return array( 'success' => false, 'message' => "Backup file {$backup_path} not found." );
        }

        // Restore .bak to the original file
        $restored = copy( $backup_path, $original_filepath );

        if ( $restored ) {
            // Clean up backup and meta to prevent duplicate undo
            unlink( $backup_path );
            unlink( $meta_file );

            $this->log( "[UNDO] Restored {$original_filepath} from backup." );
            return array( 'success' => true, 'message' => "Successfully restored {$original_filepath}." );
        } else {
            $this->log( "[UNDO Error] Failed to restore {$original_filepath} from backup." );
            return array( 'success' => false, 'message' => "Failed to restore {$original_filepath}." );
        }
    }

    /**
     * Log actions to activity.log
     *
     * @param string $message The message to log.
     */
    public function log( $message ) {
        $log_file = plugin_dir_path( __FILE__ ) . 'activity.log';
        $timestamp = current_time( 'mysql' );
        $log_entry = "[{$timestamp}] {$message}" . PHP_EOL;

        file_put_contents( $log_file, $log_entry, FILE_APPEND );
    }
}
