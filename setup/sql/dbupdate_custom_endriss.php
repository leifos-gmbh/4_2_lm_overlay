<#1>
<?php
/** @var $ilDB ilDB */
if(!$ilDB->tableColumnExists('file_data', 'pre_download_hook'))
{
	$ilDB->addTableColumn(
		'file_data',
		'pre_download_hook',
		array(
			'type'    => 'integer',
			'length'  => 1,
			'default' => 0,
			'notnull' => true
		)
	);
}
?>