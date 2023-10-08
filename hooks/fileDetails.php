<?php
//
// Description
// -----------
// Function to return the filename for an ID
//
// Arguments
// ---------
// user_id:         The user making the request
// 
// 
// Returns
// -------
//
function ciniki_files_hooks_fileDetails($ciniki, $tnid, $args) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');

    if( isset($args['file_id']) && $args['file_id'] != '' ) {
        $strsql = "SELECT ciniki_files.id, "
            . "ciniki_files.filename, "
            . "ciniki_files.content_type, "
            . "ciniki_files.extension, "
            . "ciniki_files.checksum "
            . "FROM ciniki_files "
            . "WHERE ciniki_files.id = '" . ciniki_core_dbQuote($ciniki, $args['file_id']) . "' "
            . "AND ciniki_files.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.files', 'file');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.files.4', 'msg'=>'Unable to find file', 'err'=>$rc['err']));
        }
        if( !isset($rc['file']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.files.5', 'msg'=>'Unable to find file'));
        }
        $file = $rc['file'];
        return array('stat'=>'ok', 'file'=>$file);    
    }

    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.files.6', 'msg'=>'No file specified'));
}
