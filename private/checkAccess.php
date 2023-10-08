<?php
//
// Description
// ===========
//
// Arguments
// =========
// tnid:         The ID of the tenant the request is for.
// 
// Returns
// =======
//
function ciniki_files_checkAccess($ciniki, $tnid, $method) {
    
    //
    // This module is enabled for all tenants
    //

    //
    // Sysadmins are allowed full access, except for deleting.
    //
    if( ($ciniki['session']['user']['perms'] & 0x01) == 0x01 ) {
        return array('stat'=>'ok');
    }

    //
    // Users who are an owner or employee of a tenant can see the tenant images
    //
    if( $tnid > 0 ) {
        $strsql = "SELECT tnid, user_id FROM ciniki_tenant_users "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "' "
            . "AND package = 'ciniki' "
            . "AND status = 10 "
            . "AND (permission_group = 'owners' OR permission_group = 'employees' OR permission_group = 'resellers' ) "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.tenants', 'user');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        
        //
        // If the user has permission, return ok
        //
        if( isset($rc['rows']) && isset($rc['rows'][0]) 
            && $rc['rows'][0]['user_id'] > 0 && $rc['rows'][0]['user_id'] == $ciniki['session']['user']['id'] ) {
            return array('stat'=>'ok');
        }
    } else {
        return array('stat'=>'ok');
    }

    //
    // By default, fail
    //
    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.files.26', 'msg'=>'Access denied.'));
}
?>
