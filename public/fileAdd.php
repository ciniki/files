<?php
//
// Description
// -----------
// This method will add a new file for the tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to add the File to.
//
// Returns
// -------
//
function ciniki_files_fileAdd(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'files', 'private', 'checkAccess');
    $rc = ciniki_files_checkAccess($ciniki, $args['tnid'], 'ciniki.files.fileAdd');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the tenant storage directory
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'hooks', 'storageDir');
    $rc = ciniki_tenants_hooks_storageDir($ciniki, $args['tnid'], array());
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $tenant_storage_dir = $rc['storage_dir'];

    if( isset($_FILES['uploadfile']['error']) && $_FILES['uploadfile']['error'] == UPLOAD_ERR_INI_SIZE ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.files.1', 'msg'=>'Upload failed, file to large'));
    }
    elseif( isset($_FILES['uploadfile']['error']) && $_FILES['uploadfile']['error'] > 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.files.2', 'msg'=>'Upload failed, unknown error'));
    }
    
    if( !isset($_FILES['uploadfile']['tmp_name']) || $_FILES['uploadfile']['tmp_name'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.files.3', 'msg'=>'Upload failed, no file specified.'));
    }

    //
    // Setup file arguments
    //
    $args['checksum'] = hash_file('md5', $_FILES['uploadfile']['tmp_name']);
    $args['filename'] = $_FILES['uploadfile']['name'];
    $args['extension'] = preg_replace('/^.*\.([a-zA-Z]+)$/', '$1', $args['filename']);
    $args['contnt_type'] = '';

    $finfo = finfo_open(FILEINFO_MIME);
    if( $finfo ) {
        $args['content_type'] = finfo_file($finfo, $_FILES['uploadfile']['tmp_name']);
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');
    $args['permalink'] = ciniki_core_makePermalink($ciniki, $args['filename']);

    //
    // Get a UUID for use in permalink
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUUID');
    $rc = ciniki_core_dbUUID($ciniki, 'ciniki.files');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.files.11', 'msg'=>'Unable to get a new UUID', 'err'=>$rc['err']));
    }
    $args['uuid'] = $rc['uuid'];

    $storage_dirname = $tenant_storage_dir . '/ciniki.files/' . $args['uuid'][0];
    $storage_filename = $storage_dirname . '/' . $args['uuid'];

    //
    // Move the file into the storage directory
    //
    if( !is_dir($storage_dirname) ) {
        if( !mkdir($storage_dirname, 0700, true) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.files.12', 'msg'=>'Unable to store file'));
        }
    }
    if( !rename($_FILES['uploadfile']['tmp_name'], $storage_filename) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.files.13', 'msg'=>'Unable to store file'));
    }

    //
    // Check for a duplicate file
    //
    $strsql = "SELECT id, filename "
        . "FROM ciniki_files "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND checksum = '" . ciniki_core_dbQuote($ciniki, $args['checksum']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.files', 'file');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.files.14', 'msg'=>'Unable to load file', 'err'=>$rc['err']));
    }
    if( isset($rc['rows']) && count($rc['rows']) > 0 ) {
        return array('stat'=>'ok', 'id'=>$rc['rows'][0]['id'], 'filename'=>$rc['rows'][0]['filename']);
    }

    //
    // Start transaction
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.files');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Add the file to the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.files.file', $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.files');
        return $rc;
    }
    $file_id = $rc['id'];

    //
    // Commit the transaction
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.files');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the last_change date in the tenant modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'updateModuleChangeDate');
    ciniki_tenants_updateModuleChangeDate($ciniki, $args['tnid'], 'ciniki', 'files');

    //
    // Update the web index if enabled
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'hookExec');
    ciniki_core_hookExec($ciniki, $args['tnid'], 'ciniki', 'web', 'indexObject', array('object'=>'ciniki.files.file', 'object_id'=>$file_id));

    return array('stat'=>'ok', 'id'=>$file_id, 'filename'=>$args['filename']);
}
?>
