<?php
//
// Description
// ===========
// This method will return the file in it's binary form.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:     The ID of the tenant the requested file belongs to.
// file_id:         The ID of the file to be downloaded.
//
// Returns
// -------
// Binary file.
//
function ciniki_files_fileDownload($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'file_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'File'), 
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];
    
    //  
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'files', 'private', 'checkAccess');
    $rc = ciniki_files_checkAccess($ciniki, $args['tnid'], 'ciniki.files.fileDownload'); 
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

    //
    // Load the file
    //
    $strsql = "SELECT ciniki_files.id, "
        . "ciniki_files.uuid, "
        . "ciniki_files.filename, "
        . "ciniki_files.content_type, "
        . "ciniki_files.extension, "
        . "ciniki_files.checksum "
        . "FROM ciniki_files "
        . "WHERE ciniki_files.id = '" . ciniki_core_dbQuote($ciniki, $args['file_id']) . "' "
        . "AND ciniki_files.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.files', 'file');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.files.7', 'msg'=>'Unable to find file', 'err'=>$rc['err']));
    }
    if( !isset($rc['file']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.files.8', 'msg'=>'Unable to find file'));
    }
    $file = $rc['file'];

    //
    // Set the file storage filename
    //
    $storage_filename = $tenant_storage_dir . '/ciniki.files/' . $file['uuid'][0] . '/' . $file['uuid'];
    if( !is_file($storage_filename) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.files.9', 'msg'=>'Unable to find file'));
    }

    header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); 
    header("Last-Modified: " . gmdate("D,d M YH:i:s") . " GMT"); 
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');
    // Set mime header
    $finfo = finfo_open(FILEINFO_MIME);
    if( $finfo ) { header('Content-Type: ' . finfo_file($finfo, $storage_filename)); }
    // Specify Filename
    header('Content-Disposition: attachment;filename="' . $file['filename'] . '"');
    header('Content-Length: ' . filesize($storage_filename));
    header('Cache-Control: max-age=0');

    $fp = fopen($storage_filename, 'rb');
    fpassthru($fp);

    return array('stat'=>'exit');
}
?>
