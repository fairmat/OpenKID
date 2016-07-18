<?php
/**
* This file contains utility classes to access easily OpenKID repositories
* in order to extract information from them
*
* LICENCE:
* Copyright (c) 2016 Fairmat SRL.
* Permission is hereby granted, free of charge, to any person obtaining
* a copy of this software and associated documentation files
* (the "Software"), to deal in the Software without restriction,
* including without limitation the rights to use, copy, modify, merge,
* publish, distribute, sublicense, and/or sell copies of the Software,
* and to permit persons to whom the Software is furnished to do so,
* subject to the following conditions:
* 
* The above copyright notice and this permission notice shall be included
* in all copies or substantial portions of the Software.
* 
* THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
* EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
* OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
* IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY
* CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT,
* TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE
* SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*
* @copyright  Copyright (c) 2016 Fairmat SRL (http://www.fairmat.com)
* @license    MIT.
* @version    0.1
*/


/**
* An implementation of several utils to access OpenKID repositories.
*
* @copyright  Copyright (c) 2016 Fairmat SRL (http://www.fairmat.com).
* @license    MIT.
* @version    0.1.
*/
class OpenKIDUtils
{
	/**
	 * Inner function that loads an XML file from an url (can be a file too).
	 * 
	 * @param indexUrl The position of the xml file to open.
	 * @return A simplexml parsed XML file object.
	 */
	private static function loadXML($indexUrl)
	{
		$XMLData = "";
		// This requires allow_url_fopen but allows to open also file
		// system files so easier for development.
		// You can make it always true in case you only open from local
		// file system.
		if(ini_get('allow_url_fopen'))
		{
			// As url fopen is available try to access open it directly
			$XMLData = file_get_contents($indexUrl);
			
			// Check if we got something.
			if(!$XMLData)
			{
				throw new Exception("Specified file not found.");
			}
		}
		else
		{
			// TODO: implement curl access
			throw new Exception('Only allow_url_fopen is supported at the moment.');
		}
		
		// Try to parse the string as xml and construct a tree.
		$XML = simplexml_load_string($XMLData);
		
		// If it faled throw an exception
		if(!$XML)
		{
			throw new Exception("XML couldn't be parsed");
		}

		return $XML;
	}
	
	/**
	 * Inner function used to parse <Versions>...</Versions> data
	 * as the format is shared between the history and main index files.
	 * 
	 * @param item A simplexml <Versions> element.
	 * @returns A list of all versions in a dictionary with the language
	 *          as key and the address of the pdf for that version as value.
	 */
	private static function parseVersionsEntry($item)
	{
		$Versions = [];
		foreach($item->Versions->children() as $version)
		{
			$Versions[(string)$version->Language] = (string)$version->Url;
		}
		
		return $Versions;
	}
	
	/**
	 * Inner function which extracts a single KID entry from the index file.
	 * 
	 * @param item A simple XML element pointing related to a <KIDEntry>
	 * @return A dictionary containing the attribute as dictionary key
	 *         and the attribute value as dictionary value.
	 * 
	 */
	private static function parseSingleKIDEntry($item)
	{
		// Generate a dictionary containing the data
		// The dictionary key correspond to the keys
		// in the xml.
		$KID = [];
		
		// First retrieve some simple attributes,
		// history file is ignored as not needed.
		$KID['Issuer'] = (string)$item->Issuer[0];
		$KID['ISIN'] = (string)$item->ISIN[0];
		$KID['ProductName'] = (string)$item->ProductName[0];
		
		// These two items will have current time as 00:00:00 as we
		// don't have that information.
		$KID['FirstPublished'] = DateTime::createFromFormat('Y-m-d', $item->FirstPublished[0])->setTime(0, 0, 0);
		$KID['LastUpdated'] = DateTime::createFromFormat('Y-m-d', $item->LastUpdated[0])->setTime(0, 0, 0);
		
		// Now extract the versions with language marking as dictionary entry.
		$KID['Versions'] = self::parseVersionsEntry($item);
		
		return $KID;
	}
		
	/**
	 * Gets a list of all KIDs in the index of the provided repository url.
	 * 
	 * @param repoUrl The URL of the repository to check for KIDs 
	 *               (check repo-example for an idea of its structure).
	 * @return A list of all KIDs, each KID is represented as a dictionary
	 *         containing its various attributes.
	 */
	public static function getAllAvailableKIDs($repoUrl)
	{
		// Load the xml.
		$XML = self::loadXML($repoUrl."kidsindex.xml");
		
		// Prepare an array to contain all KID in the index.
		$KIDs = [];
		
		// Go through all KID found in the xml.
		foreach ($XML->xpath('//KIDS/KIDEntry') as $item)
		{    	
			$KIDs[] = self::parseSingleKIDEntry($item);
		}
		
		return $KIDs;
	}
	
	/**
	 * Gets a list of all KIDs in the index of the provided repository url,
	 * which have been updated or added since the provided date.
	 * 
	 * @param repoUrl The URL of the repository to check for KIDs 
	 *               (check repo-example for an idea of its structure).
	 * @param minDate The date to check against to filter out older KIDs.
	 * @return A list of all KIDs updated or added after the specified date,
	 *         each KID is represented as a dictionary
	 *         containing its various attributes.
	 */
	public static function getAllAvailableKIDsAfterDate($repoUrl, $minDate)
	{
		// As the resource difference between parsing all data and then checking
		// is minimal vs checking and then parsing what we need we'll just
		// filter the results after gathering them all.
		$referenceDate = clone $minDate;
		$referenceDate->setTime(0, 0, 0);
		$KIDs = self::getAllAvailableKIDs($repoUrl);
		$filteredKIDs = [];
		
		// Go through all KIDs and add them to the list if they are more recent.
		foreach($KIDs as $KID)
		{
			// We check both last update and first published for the filtering.
			if($KID['FirstPublished']->getTimeStamp() >=  $referenceDate->getTimeStamp() ||
			   $KID['LastUpdated']->getTimeStamp() >=  $referenceDate->getTimeStamp())
			{
				$filteredKIDs[] = $KID;
			}
		}
		
		return $filteredKIDs;
	}
	
	/**
	 * Gets the last time the repository was updated (by checking the last
	 * updated field of the index file)
	 * 
	 * @param repoUrl The URL of the repository to check for KIDs 
	 *               (check repo-example for an idea of its structure).
	 * @return A DateTime object with the last update date in it.
	 */
	public static function getLastUpdate($repoUrl)
	{
		// Load the xml.
		$XML = self::loadXML($repoUrl."kidsindex.xml");
		
		// Read the LastUpdated field and return it as a DateTime object.
		return DateTime::createFromFormat('Y-m-d', (string)$XML->children()->LastUpdated)->setTime(0,0,0);
	}
	
	/**
	 * Gets the publishing history of a specific ISIN from the history file
	 * associated to it.
	 * 
	 * This is done by first checking for the index file to contain
	 * the ISIN of the product we are interested into, then checking the
	 * reference within the history folder of the repository.
	 * That file is then parsed to provide the full history of the
	 * products we are interested into.
	 * 
	 * @param repoUrl The URL of the repository to check for KIDs 
	 *               (check repo-example for an idea of its structure).
	 * @param ISIN The product ISIN we are interested into.
	 * @returns a list of history entries provided by the correlated
	 *          history file.
	 */
	public static function getProductPublishingHistory($indexUrl, $ISIN)
	{
		// Load the xml.
		$XML = self::loadXML($indexUrl."kidsindex.xml");
		
		// Go through all KID found in the xml to find the one needed.
		foreach ($XML->xpath('//KIDS/KIDEntry') as $item)
		{
			if($ISIN == (string)$item->ISIN[0])
			{
				// Now load the history xml
				$XML = self::loadXML($indexUrl."history/".$item->History[0]);
				
				// Prepare an array to contain all KID updates in the history.
				$history = [];
		
				// Go through all KID history found in the xml.
				foreach ($XML->xpath('//History/Update') as $item)
				{    	
					$historyEntry = [];

					// Now fill from the data supplied in the xml.
					$historyEntry["PublishDate"] = DateTime::createFromFormat('Y-m-d', $item->PublishDate[0])->setTime(0, 0, 0);
					$historyEntry["Versions"] = self::parseVersionsEntry($item);
					$history[] = $historyEntry;
				}

				return $history;
			}
		}
	}
}
?>
