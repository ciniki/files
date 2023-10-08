<?php
//
// Description
// -----------
// This function returns the list of objects for the module.
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_files_objects(&$ciniki) {
    //
    // Build the objects
    //
    $objects = array();
    $objects['file'] = array(
        'name'=>'File',
        'sync'=>'yes',
        'backup'=>'no',
        'table'=>'ciniki_files',
        'o_container'=>'files',
        'o_name'=>'file',
        'fields'=>array(
            'filename'=>array('name'=>'Filename'),
            'permalink'=>array('name'=>'Permalink'),
            'content_type'=>array('name'=>'File Type', 'default' => ''),
            'extension'=>array('name'=>'Extension'),
            'checksum'=>array('name'=>'Checksum'),
            ),
        'history_table'=>'ciniki_image_history',
        );

    return array('stat'=>'ok', 'objects'=>$objects);
}
?>
