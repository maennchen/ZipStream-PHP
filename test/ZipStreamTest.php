<?php
namespace ZipStreamTest;
use \ZipStream\ZipStream;
use \PHPUnit_Framework_TestCase;

/**
 * Test Class for the Main ZipStream CLass
 * 
 * @author Jonatan Männchen <jonatan@maennchen.ch>
 * @copyright Copyright (c) 2014, Jonatan Männchen
 */
class ZipStreamTest extends PHPUnit_Framework_TestCase {
	/**
	 * @expectedException ZipStream\Exception\InvalidOptionException
	 */
	public function testInvalidOptionException() {
		// Get ZipStream Object
		$zip = new ZipStream();

		// Set large_file_size very small to be able to test add_large_file method
		$zip->opt['large_file_size'] = 5;

		// Set large_file_method to a wrong value
		$zip->opt['large_file_method'] = 'xy';

		// Trigger error by adding a file
		$zip->addFileFromPath('foobar.php', __FILE__); 
	}
}