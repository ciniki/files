<?php
//
// Description
// -----------
// This function returns the int to text mappings for the module.
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_files_maps(&$ciniki) {
    //
    // Build the maps object
    //
    $maps = array();
    $maps['object'] = array(
        'field' => array(
            'int'=>'text',
        ),
    );
    //
    return array('stat'=>'ok', 'maps'=>$maps);
}
?>
